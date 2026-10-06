<?php

namespace App\Services\Widget;

use App\Enums\ConversationChannelType;
use App\Enums\ConversationStatus;
use App\Enums\ConversationType;
use App\Enums\MessageSenderType;
use App\Models\AiAgentSetting;
use App\Models\AiConversationMeta;
use App\Models\AiUnansweredQuestion;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\LyroAiEngine;
use App\Services\UsageLimiter;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Lyro answering inside widget conversations.
 *
 * Silent (leaves the chat to the team) whenever: Lyro is off, the Channels / Audience rules say
 * no, a human already replied or was assigned, the chat was already handed off, the conversation
 * is solved/spam, the AI engine is not configured, or the API call fails.
 */
class LyroWidgetResponder
{
    /** Newest visitor messages that may be answered in one run (a visitor can send several quickly). */
    private const MAX_ROUNDS = 3;

    private const FALLBACK_HANDOFF = "Let me get a teammate to help you with this. They'll reply here shortly.";

    private const FALLBACK_UNKNOWN = "I'm not sure about that one — I'll let a teammate know so they can follow up.";

    public function __construct(
        private readonly LyroAiEngine $engine,
        private readonly WidgetConfigBuilder $config,
        private readonly UsageLimiter $usage,
    ) {
    }

    public function respond(int $messageId): void
    {
        $message = Message::query()->select(['id', 'conversation_id'])->find($messageId);

        if (! $message) {
            return;
        }

        set_time_limit(120); // the API call alone may take up to 30 s

        try {
            // One reply at a time per conversation, so two quick messages never get two answers.
            Cache::lock('lyro-widget:'.$message->conversation_id, 120)->block(60, function () use ($messageId) {
                $next = $messageId;

                for ($round = 0; $round < self::MAX_ROUNDS && $next !== null; $round++) {
                    $next = $this->answer($next);
                }
            });
        } catch (LockTimeoutException) {
            // Another worker is already answering this conversation.
        } catch (Throwable $e) {
            report($e); // never break the visitor's chat because the bot failed
        }
    }

    /**
     * Answers one visitor message. Returns the id of a NEWER visitor message that still needs
     * an answer (so the caller can continue with it), or null when there is nothing left to do.
     */
    private function answer(int $messageId): ?int
    {
        $message = Message::query()->with('conversation')->find($messageId);

        if (! $message
            || $message->sender_type !== MessageSenderType::Visitor
            || $message->is_private_note
            || ! $message->conversation) {
            return null;
        }

        $answeredKey = 'lyro-widget:answered:'.$message->id;

        if (Cache::has($answeredKey)) {
            return null; // another run already covered this message
        }

        $conversation = $message->conversation;

        // A newer visitor message exists: answer that one instead — it sees the whole thread.
        $newer = $conversation->messages()
            ->where('sender_type', MessageSenderType::Visitor->value)
            ->where('is_private_note', false)
            ->where('id', '>', $message->id)
            ->orderBy('id')
            ->value('id');

        if ($newer) {
            // Never answer an older message once a newer one exists: hand over to the newer one,
            // or stop if it was already answered.
            return Cache::has('lyro-widget:answered:'.$newer) ? null : (int) $newer;
        }

        $setting = $this->eligibleSetting($conversation);

        if (! $setting) {
            return null;
        }

        $workspace = $conversation->workspace;
        $history = $this->history($conversation);

        if ($history === []) {
            return null;
        }

        try {
            $result = $this->engine->converse($workspace, $history, 'live');
        } catch (Throwable $e) {
            report($e);

            return null; // stay silent; the team still sees the visitor's message in the Inbox
        }

        Cache::put($answeredKey, true, now()->addMinutes(10));

        $rules = $setting->handoff_rules ?? [];
        $handoff = $result['handoff']
            || ($result['unknown'] && ($rules['on_low_confidence'] ?? true));

        $text = $result['text'] !== ''
            ? $result['text']
            : ($handoff ? self::FALLBACK_HANDOFF : self::FALLBACK_UNKNOWN);

        $saved = $this->save($conversation, $message, $text, $handoff, $result['unknown']);

        if (! $saved) {
            return null;
        }

        // A visitor message that arrived while the API call was running still needs an answer.
        $pending = $conversation->messages()
            ->where('sender_type', MessageSenderType::Visitor->value)
            ->where('is_private_note', false)
            ->where('id', '>', $message->id)
            ->orderByDesc('id')
            ->value('id');

        return ($pending && ! $handoff) ? (int) $pending : null;
    }

    /** The workspace's Lyro settings if Lyro may answer THIS conversation right now, else null. */
    private function eligibleSetting(Conversation $conversation): ?AiAgentSetting
    {
        if ($conversation->channel_type !== ConversationChannelType::Widget
            || $conversation->type !== ConversationType::Chat
            || ! in_array($conversation->status, [ConversationStatus::Open, ConversationStatus::Pending], true)) {
            return null;
        }

        // A human owns the chat: assigned, already replied, or Lyro already handed it off.
        if ($conversation->assigned_operator_id !== null
            || $conversation->messages()->where('sender_type', MessageSenderType::Operator->value)->exists()
            || $conversation->aiMeta()->whereNotNull('handed_off_at')->exists()) {
            return null;
        }

        if (! $this->engine->isAvailable()) {
            return null;
        }

        $setting = AiAgentSetting::query()->where('workspace_id', $conversation->workspace_id)->first();

        if (! $setting || ! $setting->is_active) {
            return null;
        }

        $channels = $setting->channel_rules ?? [];

        if (! ($channels['live_answer_enabled'] ?? true)) {
            return null;
        }

        // Plan limit: a conversation Lyro already joined may finish, but a NEW one needs room left
        // in this month's `ai_conversations` allowance (0 = AI not included in the plan).
        $alreadyJoined = $conversation->messages()->where('sender_type', MessageSenderType::Bot->value)->exists();

        if (! $alreadyJoined && ! $this->usage->hasRoom($conversation->workspace, 'ai_conversations')) {
            return null;
        }

        if ($channels['live_outside_hours_only'] ?? false) {
            $website = $conversation->workspace->websites()->first();

            if ($website && $this->config->isOnline($website)) {
                return null; // team is online — Lyro only covers outside operating hours
            }
        }

        return $this->audienceAllows($conversation, $setting) ? $setting : null;
    }

    private function audienceAllows(Conversation $conversation, AiAgentSetting $setting): bool
    {
        $contactId = $conversation->contact_id ?? $conversation->visitor?->contact_id;

        // There is no country data for visitors yet, so "specific countries" can't be honoured:
        // stay silent rather than answer people the owner meant to exclude.
        $answerFor = $setting->audience_answer_for ?: 'everyone';

        if ($answerFor === 'specific_countries') {
            return false;
        }

        if ($answerFor === 'logged_in' && $contactId === null) {
            return false;
        }

        $excludeTag = trim((string) $setting->audience_exclude_tag);

        if ($excludeTag !== '' && $contactId !== null) {
            $tagged = DB::table('taggables')
                ->join('tags', 'tags.id', '=', 'taggables.tag_id')
                ->where('taggables.taggable_type', (new \App\Models\Contact)->getMorphClass())
                ->where('taggables.taggable_id', $contactId)
                ->where('tags.workspace_id', $conversation->workspace_id)
                ->whereRaw('LOWER(tags.name) = ?', [mb_strtolower($excludeTag)])
                ->exists();

            if ($tagged) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recent visitor/bot turns as Claude messages: oldest first, starting with a user turn,
     * consecutive turns from the same side merged.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        $limit = (int) config('widget.lyro.history', 20);

        $rows = $conversation->messages()
            ->where('is_private_note', false)
            ->whereIn('sender_type', [MessageSenderType::Visitor->value, MessageSenderType::Bot->value])
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['sender_type', 'body'])
            ->reverse()
            ->values();

        $turns = [];

        foreach ($rows as $row) {
            $body = trim((string) $row->body);

            if ($body === '') {
                continue;
            }

            $role = $row->sender_type === MessageSenderType::Visitor ? 'user' : 'assistant';

            if ($turns === [] && $role === 'assistant') {
                continue;
            }

            if ($turns !== [] && end($turns)['role'] === $role) {
                $turns[array_key_last($turns)]['content'] .= "\n".$body;

                continue;
            }

            $turns[] = ['role' => $role, 'content' => $body];
        }

        return $turns;
    }

    private function save(Conversation $conversation, Message $trigger, string $text, bool $handoff, bool $unknown): bool
    {
        DB::beginTransaction();

        try {
            $conversation = Conversation::query()->whereKey($conversation->id)->lockForUpdate()->first();

            // A teammate may have jumped in while the AI was thinking — then Lyro says nothing.
            if (! $conversation
                || $conversation->assigned_operator_id !== null
                || ! in_array($conversation->status, [ConversationStatus::Open, ConversationStatus::Pending], true)
                || $conversation->messages()->where('sender_type', MessageSenderType::Operator->value)->exists()) {
                DB::rollBack();

                return false;
            }

            $firstBotReply = ! $conversation->messages()->where('sender_type', MessageSenderType::Bot->value)->exists();

            $message = $conversation->messages()->create([
                'sender_type' => MessageSenderType::Bot,
                'sender_id' => null,
                'body' => $text,
                'is_private_note' => false,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            if ($firstBotReply) {
                $this->usage->record($conversation->workspace, 'ai_conversations'); // once per conversation
            }

            if ($unknown) {
                $this->logUnanswered($conversation, (string) $trigger->body);
            }

            if ($handoff) {
                AiConversationMeta::query()->updateOrCreate(
                    ['conversation_id' => $conversation->id],
                    ['handed_off_at' => now()],
                );

                if ($conversation->status === ConversationStatus::Pending) {
                    $conversation->update(['status' => ConversationStatus::Open]); // needs a human now
                }
            }

            activity('widget')
                ->performedOn($message)
                ->event('created')
                ->withProperties(['conversation_id' => $conversation->id, 'handoff' => $handoff, 'unknown' => $unknown])
                ->log('Lyro replied in chat widget'); // never log the body

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return true;
    }

    /** Feeds the Lyro → Suggestions tab. Deduped by exact text, like the existing table expects. */
    private function logUnanswered(Conversation $conversation, string $question): void
    {
        $question = mb_substr(trim(preg_replace('/\s+/', ' ', $question)), 0, 255);

        if ($question === '') {
            return;
        }

        $existing = AiUnansweredQuestion::query()
            ->where('workspace_id', $conversation->workspace_id)
            ->where('question', $question)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            $existing->increment('asked_count');

            return;
        }

        AiUnansweredQuestion::create([
            'workspace_id' => $conversation->workspace_id,
            'question' => $question,
            'asked_count' => 1,
        ]);
    }
}
