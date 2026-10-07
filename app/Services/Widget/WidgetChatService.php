<?php

namespace App\Services\Widget;

use App\Enums\ConversationChannelType;
use App\Enums\ConversationPriority;
use App\Enums\ConversationStatus;
use App\Enums\ConversationType;
use App\Enums\MessageSenderType;
use App\Exceptions\ConversationLimitReached;
use App\Jobs\ReplyWithLyro;
use App\Models\AiAgentSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Website;
use App\Models\Workspace;
use App\Services\AttachmentService;
use App\Services\UsageLimiter;
use App\Support\Countries;
use App\Support\UserAgentSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * All business logic behind the public chat widget API. Controllers stay thin and only
 * translate HTTP to these calls. Every query is scoped by the visitor's workspace_id.
 */
class WidgetChatService
{
    private const HISTORY_LIMIT = 100;

    /** Don't write to `visitors` on every 3-second poll — only when something meaningful changed. */
    private const HEARTBEAT_SECONDS = 30;

    public function __construct(
        private readonly UsageLimiter $usage,
        private readonly AttachmentService $attachments,
    ) {
    }

    // ------------------------------------------------------------------ sessions

    public function startOrResumeSession(
        Website $website,
        ?string $sessionId,
        ?string $ip,
        ?string $userAgent,
        ?string $pageUrl,
        ?string $countryCode = null,
    ): Visitor {
        $visitor = null;

        if ($sessionId !== null && Str::isUuid($sessionId)) {
            $visitor = Visitor::query()
                ->where('workspace_id', $website->workspace_id)
                ->where('session_id', $sessionId)
                ->first();
        }

        $countryCode = Countries::has($countryCode) ? strtoupper((string) $countryCode) : null;

        if ($visitor) {
            $this->touch($visitor, $pageUrl);
            $this->rememberCountry($visitor, $countryCode);

            return $visitor;
        }

        DB::beginTransaction();

        try {
            $visitor = Visitor::create([
                'workspace_id' => $website->workspace_id,
                'session_id' => (string) Str::uuid(), // server-issued: it doubles as the visitor's bearer token
                'ip_address' => $ip,
                'country_code' => $countryCode,
                'location' => Countries::name($countryCode),
                'browser' => UserAgentSummary::browser($userAgent),
                'os' => UserAgentSummary::os($userAgent),
                'current_page' => $this->cleanUrl($pageUrl),
                'is_online' => true,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);

            activity('widget')
                ->performedOn($visitor)
                ->event('created')
                ->withProperties(['website_id' => $website->id])
                ->log('Widget visitor session started');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $visitor;
    }

    /** Saves the detected country when it is new or changed (e.g. the visitor switched VPN). */
    private function rememberCountry(Visitor $visitor, ?string $countryCode): void
    {
        if ($countryCode === null || $visitor->country_code === $countryCode) {
            return;
        }

        $visitor->update([
            'country_code' => $countryCode,
            'location' => Countries::name($countryCode),
        ]);
    }

    /** Heartbeat: keeps the visitor "online" and their current page fresh, with minimal writes. */
    public function touch(Visitor $visitor, ?string $pageUrl = null): void
    {
        $page = $this->cleanUrl($pageUrl);

        $stale = $visitor->last_seen_at === null
            || $visitor->last_seen_at->lt(now()->subSeconds(self::HEARTBEAT_SECONDS));
        $pageChanged = $page !== null && $page !== $visitor->current_page;

        if (! $stale && ! $pageChanged && $visitor->is_online) {
            return;
        }

        $visitor->update([
            'is_online' => true,
            'last_seen_at' => now(),
            'current_page' => $page ?? $visitor->current_page,
        ]);
    }

    // ------------------------------------------------------------------ reading

    /** What the chat frame renders on load: the open conversation (if any) and its history. */
    public function snapshot(Visitor $visitor): array
    {
        $conversation = $this->activeConversation($visitor);

        $messages = $conversation
            ? $conversation->messages()
                ->where('is_private_note', false)
                ->orderByDesc('id')
                ->limit(self::HISTORY_LIMIT)
                ->get()
                ->reverse()
                ->values()
            : collect();

        return [
            'conversation' => $conversation ? $this->conversationPayload($conversation) : null,
            'messages' => $this->present($visitor->workspace_id, $messages),
            'identified' => $visitor->contact_id !== null,
        ];
    }

    /** Messages newer than $afterId in the visitor's latest conversation. */
    public function poll(Visitor $visitor, int $afterId, ?string $pageUrl = null): array
    {
        $this->touch($visitor, $pageUrl);

        $conversation = $this->latestConversation($visitor);

        if (! $conversation) {
            return ['conversation' => null, 'messages' => []];
        }

        $messages = $conversation->messages()
            ->where('is_private_note', false) // internal notes never leave the Inbox
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        return [
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $this->present($visitor->workspace_id, $messages),
        ];
    }

    // ------------------------------------------------------------------ writing

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files  already validated by SendMessageRequest
     *
     * @throws ConversationLimitReached when a NEW conversation is needed but the plan's monthly limit is used up
     */
    public function sendVisitorMessage(Visitor $visitor, ?string $body, ?string $pageUrl = null, array $files = []): array
    {
        $stored = [];

        DB::beginTransaction();

        try {
            // Serialise concurrent sends from one visitor (two tabs, double click)
            // so they land in the same conversation instead of creating two.
            Visitor::query()->whereKey($visitor->id)->lockForUpdate()->first();

            $conversation = $this->activeConversation($visitor);

            if (! $conversation) {
                $this->guardConversationLimit($visitor->workspace_id);

                $conversation = Conversation::create([
                    'workspace_id' => $visitor->workspace_id,
                    'channel_type' => ConversationChannelType::Widget,
                    'contact_id' => $visitor->contact_id,
                    'visitor_id' => $visitor->id,
                    'type' => ConversationType::Chat,
                    'status' => ConversationStatus::Open,
                    'priority' => ConversationPriority::Normal,
                ]);

                activity('widget')
                    ->performedOn($conversation)
                    ->event('created')
                    ->withProperties(['visitor_id' => $visitor->id])
                    ->log('Conversation started from chat widget');
            } elseif ($conversation->status === ConversationStatus::Pending) {
                // The visitor answered — it needs the team's attention again.
                $conversation->update(['status' => ConversationStatus::Open]);
            }

            $stored = $files === []
                ? []
                : $this->attachments->store($files, $visitor->workspace_id, $conversation->id);

            $message = $conversation->messages()->create([
                'sender_type' => MessageSenderType::Visitor,
                'sender_id' => $visitor->id,
                'body' => $body,
                'attachments' => $stored ?: null,
                'is_private_note' => false,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            activity('widget')
                ->performedOn($message)
                ->event('created')
                ->withProperties(['conversation_id' => $conversation->id, 'attachments' => count($stored)]) // never log the body or file names
                ->log('Visitor message sent from chat widget');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->attachments->discard($stored); // the message was not saved, so its files must not linger

            throw $e;
        }

        $this->touch($visitor, $pageUrl);
        $this->askLyro($message);

        return [
            'message' => $this->present($visitor->workspace_id, collect([$message]))[0],
            'conversation' => $this->conversationPayload($conversation->refresh()),
        ];
    }

    /**
     * Optional "leave your email" step. Attaches (or creates) a Contact and links it to the
     * visitor and their open conversation. Existing contact data is never overwritten or exposed.
     */
    public function identify(Visitor $visitor, ?string $name, ?string $email): void
    {
        DB::beginTransaction();

        try {
            $contact = $visitor->contact_id ? Contact::query()
                ->where('workspace_id', $visitor->workspace_id)
                ->find($visitor->contact_id) : null;

            if (! $contact && $email) {
                $contact = Contact::query()
                    ->where('workspace_id', $visitor->workspace_id)
                    ->where('email', $email)
                    ->first();
            }

            if (! $contact) {
                $contact = Contact::create([
                    'workspace_id' => $visitor->workspace_id,
                    'name' => $name,
                    'email' => $email,
                    'source' => 'widget',
                ]);

                activity('widget')
                    ->performedOn($contact)
                    ->event('created')
                    ->withProperties(['visitor_id' => $visitor->id])
                    ->log('Contact created from chat widget');
            } else {
                $changes = [];

                if ($name && ! $contact->name) {
                    $changes['name'] = $name;
                }
                if ($email && ! $contact->email) {
                    $changes['email'] = $email;
                }

                if ($changes) {
                    $contact->update($changes);

                    activity('widget')
                        ->performedOn($contact)
                        ->event('updated')
                        ->withProperties(['fields' => array_keys($changes)])
                        ->log('Contact details completed from chat widget');
                }
            }

            if ($visitor->contact_id !== $contact->id) {
                $visitor->update(['contact_id' => $contact->id]);
            }

            Conversation::query()
                ->where('workspace_id', $visitor->workspace_id)
                ->where('visitor_id', $visitor->id)
                ->whereNull('contact_id')
                ->update(['contact_id' => $contact->id]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Hard block: a visitor may not START a conversation once this month's `conversations` allowance
     * is used up (no `conversations` key in the plan = unlimited). Must run inside the caller's
     * transaction: it locks the workspace row so two visitors starting a chat at the same moment
     * can't both squeeze through the last slot (the observer counts the new conversation before
     * the lock is released).
     */
    private function guardConversationLimit(int $workspaceId): void
    {
        $workspace = Workspace::query()->whereKey($workspaceId)->lockForUpdate()->first();

        if ($workspace && ! $this->usage->hasRoom($workspace, 'conversations')) {
            throw new ConversationLimitReached;
        }
    }

    /**
     * Lets Lyro answer the visitor's message AFTER the HTTP response is sent (or on the queue),
     * so a slow AI call never delays the visitor's send. The responder decides whether it may reply.
     */
    private function askLyro(Message $message): void
    {
        if (! config('widget.lyro.enabled', true)) {
            return;
        }

        try {
            config('widget.lyro.dispatch', 'after_response') === 'queue'
                ? ReplyWithLyro::dispatch($message->id)
                : ReplyWithLyro::dispatchAfterResponse($message->id);
        } catch (Throwable $e) {
            report($e); // the visitor's message is already saved — never fail the send over the bot
        }
    }

    /** The conversation the visitor is currently chatting in (open / pending). */
    private function activeConversation(Visitor $visitor): ?Conversation
    {
        return $this->visitorConversations($visitor)
            ->whereIn('status', [ConversationStatus::Open->value, ConversationStatus::Pending->value])
            ->first();
    }

    /** Latest conversation regardless of status (so a "solved" notice can reach the widget), minus spam. */
    private function latestConversation(Visitor $visitor): ?Conversation
    {
        return $this->visitorConversations($visitor)
            ->where('status', '!=', ConversationStatus::Spam->value)
            ->first();
    }

    private function visitorConversations(Visitor $visitor): Builder
    {
        return Conversation::query()
            ->where('workspace_id', $visitor->workspace_id)
            ->where('visitor_id', $visitor->id)
            ->where('channel_type', ConversationChannelType::Widget->value)
            ->where('type', ConversationType::Chat->value)
            ->latest('id');
    }

    private function conversationPayload(Conversation $conversation): array
    {
        return ['id' => $conversation->id, 'status' => $conversation->status->value];
    }

    /**
     * Shapes messages for the widget. Operator names are fetched in ONE query for the whole
     * batch (Message::sender() is conditional, so it can't be eager loaded).
     *
     * @param  Collection<int, Message>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function present(int $workspaceId, Collection $messages): array
    {
        $operatorIds = $messages
            ->filter(fn (Message $m) => $m->sender_type === MessageSenderType::Operator && $m->sender_id)
            ->pluck('sender_id')
            ->unique();

        $operatorNames = $operatorIds->isEmpty()
            ? collect()
            : User::withTrashed()->whereIn('id', $operatorIds)->pluck('name', 'id');

        $botName = $messages->contains(fn (Message $m) => $m->sender_type === MessageSenderType::Bot)
            ? (AiAgentSetting::query()->where('workspace_id', $workspaceId)->value('agent_name') ?: 'Assistant')
            : null;

        $ttl = now()->addMinutes(max(1, (int) config('widget.attachments.url_ttl_minutes', 360)));

        return $messages->map(function (Message $m) use ($operatorNames, $botName, $ttl) {
            $from = match ($m->sender_type) {
                MessageSenderType::Visitor => 'visitor',
                MessageSenderType::Operator => 'agent',
                MessageSenderType::Bot => 'bot',
                MessageSenderType::System => 'system',
            };

            return [
                'id' => $m->id,
                'from' => $from,
                'body' => (string) $m->body,
                // Signed, expiring links: <img src> can't send the session header, the signature is the proof.
                'attachments' => $this->attachments->forClient($m->attachments, fn (array $d) => URL::temporarySignedRoute(
                    'widget.attachments.show',
                    $ttl,
                    ['message' => $m->id, 'attachment' => $d['id']],
                )),
                // First name only: visitors don't need staff surnames.
                'name' => match ($from) {
                    'agent' => Str::before((string) ($operatorNames[$m->sender_id] ?? 'Support'), ' '),
                    'bot' => $botName,
                    default => null,
                },
                'at' => $m->created_at?->toIso8601String(),
            ];
        })->values()->all();
    }

    /** Drops query string + fragment (they often carry tokens) and fits the 255-char column. */
    private function cleanUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $clean = Str::before(Str::before(trim($url), '#'), '?');

        return mb_substr($clean, 0, 255);
    }
}
