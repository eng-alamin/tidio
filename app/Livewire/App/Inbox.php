<?php

namespace App\Livewire\App;

use App\Enums\ConversationChannelType;
use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Mention;
use App\Models\Message;
use App\Models\SavedView;
use App\Notifications\MentionedInConversation;
use App\Services\AttachmentService;
use App\Services\Realtime\RealtimeConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class Inbox extends Component
{
    use WithFileUploads;

    /**
     * Which folder in the left nav is active. One of:
     * unassigned | mine | solved          (Live conversations, type=chat)
     * tickets_unassigned                  (Tickets, type=ticket)
     * mentions | lyro | spam              (More)
     * view_messenger | view_instagram | view_whatsapp   (Views, by channel, any type)
     */
    public string $folder = 'unassigned';

    /** Sub-filter for the Mentions/Lyro/Spam folders: 'live' (chat) or 'tickets' (ticket). */
    public string $moreType = 'live';

    public const MORE_FOLDERS = ['mentions', 'lyro', 'spam'];

    public ?int $selectedConversationId = null;

    public string $newMessage = '';

    public bool $isNote = false;

    /** The one file just picked in the browser; moved into $uploads by updatedNewUpload(). */
    public $newUpload = null;

    /** Files chosen for the next reply (validated, not stored until Send). */
    public array $uploads = [];

    public bool $showAssignPicker = false;

    public bool $showSaveView = false;

    public string $newViewName = '';

    public bool $newViewShared = false;

    public const VIEW_CHANNELS = [
        'view_messenger' => ConversationChannelType::Messenger,
        'view_instagram' => ConversationChannelType::Instagram,
        'view_whatsapp' => ConversationChannelType::Whatsapp,
    ];

    public function mount(): void
    {
        $this->selectedConversationId = $this->conversations->first()?->id;
    }

    /**
     * Seconds between background refreshes. With real-time (Reverb) on, pushes do the work and this
     * is just a safety net; without it, it is the old fast poll.
     */
    #[Computed]
    public function pollInterval(): string
    {
        return app(RealtimeConfig::class)->enabled() ? '30s' : '5s';
    }

    /** A real-time "conversation changed" push arrived (see layouts/app). Re-rendering re-queries everything. */
    #[On('inbox-signal')]
    public function refreshFromSignal(): void
    {
        unset($this->conversations, $this->selectedConversation, $this->mentions);
    }

    #[Computed]
    public function attachmentOptions(): array
    {
        $a = app(AttachmentService::class);

        return ['enabled' => $a->enabled(), 'max' => $a->maxFiles(), 'accept' => $a->acceptAttribute()];
    }

    /** Picked one file: check it, and add it to the list (one at a time so earlier picks are kept). */
    public function updatedNewUpload(): void
    {
        $attachments = app(AttachmentService::class);

        $this->resetErrorBag('newUpload');

        if (! $attachments->enabled() || $this->newUpload === null) {
            $this->newUpload = null;

            return;
        }

        if (count($this->uploads) >= $attachments->maxFiles()) {
            $this->addError('newUpload', 'You can attach up to '.$attachments->maxFiles().' files.');
            $this->newUpload = null;

            return;
        }

        $this->validate(
            ['newUpload' => $attachments->rules('newUpload')['newUpload.*'] ?? []],
            attributes: ['newUpload' => 'file'],
        );

        $this->uploads[] = $this->newUpload;
        $this->newUpload = null;
    }

    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    /** "live" or "tickets" — drives the Live conversations / Tickets tab above the list. */
    #[Computed]
    public function listTab(): string
    {
        if (in_array($this->folder, self::MORE_FOLDERS, true)) {
            return $this->moreType;
        }

        return $this->folder === 'tickets_unassigned' ? 'tickets' : 'live';
    }

    public function setListTab(string $tab): void
    {
        $tab = $tab === 'tickets' ? 'tickets' : 'live';

        if (in_array($this->folder, self::MORE_FOLDERS, true)) {
            $this->moreType = $tab;
            $this->selectedConversationId = $this->folder === 'mentions'
                ? $this->mentions->first()?->message?->conversation_id
                : $this->conversations->first()?->id;
            $this->showAssignPicker = false;

            return;
        }

        $this->setFolder($tab === 'tickets' ? 'tickets_unassigned' : 'unassigned');
    }

    #[Computed]
    public function conversations(): Collection
    {
        // Mentions has its own list source (Mention, not Conversation directly).
        if ($this->folder === 'mentions') {
            return collect();
        }

        $workspace = app('currentWorkspace');

        return Conversation::query()
            ->where('workspace_id', $workspace->id)
            ->when(isset(self::VIEW_CHANNELS[$this->folder]), fn ($q) => $q
                ->where('channel_type', self::VIEW_CHANNELS[$this->folder]))
            ->when($this->folder === 'tickets_unassigned', fn ($q) => $q
                ->where('type', 'ticket')
                ->whereNull('assigned_operator_id')
                ->whereNotIn('status', [ConversationStatus::Solved, ConversationStatus::Spam]))
            ->when($this->folder === 'unassigned', fn ($q) => $q
                ->where('type', 'chat')
                ->whereNull('assigned_operator_id')
                ->whereNotIn('status', [ConversationStatus::Solved, ConversationStatus::Spam]))
            ->when($this->folder === 'mine', fn ($q) => $q
                ->where('type', 'chat')
                ->where('assigned_operator_id', auth()->id())
                ->whereNotIn('status', [ConversationStatus::Solved, ConversationStatus::Spam]))
            ->when($this->folder === 'solved', fn ($q) => $q
                ->where('type', 'chat')
                ->where('status', ConversationStatus::Solved))
            ->when($this->folder === 'spam', fn ($q) => $q
                ->where('status', ConversationStatus::Spam)
                ->where('type', $this->moreType === 'tickets' ? 'ticket' : 'chat'))
            ->when($this->folder === 'lyro', fn ($q) => $q
                ->where('type', $this->moreType === 'tickets' ? 'ticket' : 'chat')
                ->whereHas('aiMeta', fn ($m) => $m->where('resolved_by_ai', true)))
            ->with(['contact', 'visitor', 'aiMeta', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->get();
    }

    /**
     * Messages where the current operator was @mentioned, newest first.
     * Scoped to this workspace via the parent conversation.
     */
    #[Computed]
    public function mentions(): Collection
    {
        $workspaceId = app('currentWorkspace')->id;

        $type = $this->moreType === 'tickets' ? 'ticket' : 'chat';

        return Mention::query()
            ->where('mentioned_user_id', auth()->id())
            ->whereHas('message.conversation', fn ($q) => $q
                ->where('workspace_id', $workspaceId)
                ->where('type', $type))
            ->with(['message.conversation.contact', 'message.conversation.visitor'])
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function selectedConversation(): ?Conversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        return Conversation::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->with(['contact', 'visitor', 'assignedOperator', 'messages' => fn ($q) => $q->oldest()])
            ->find($this->selectedConversationId);
    }

    #[Computed]
    public function operators(): Collection
    {
        return app('currentWorkspace')->users()
            ->wherePivot('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function toggleAssignPicker(): void
    {
        $this->showAssignPicker = ! $this->showAssignPicker;
    }

    public function assignOperator(int $operatorId): void
    {
        $conversation = $this->selectedConversation;

        if (! $conversation) {
            return;
        }

        // Guard against assigning someone who isn't (or no longer is) an
        // active member of this workspace — the picker only lists active
        // operators, but the id still arrives from the client.
        $isMember = $this->operators->contains('id', $operatorId);

        if (! $isMember) {
            return;
        }

        $conversation->update(['assigned_operator_id' => $operatorId]);

        $this->showAssignPicker = false;
        unset($this->selectedConversation, $this->conversations);

        $this->dispatch('toast', message: 'Conversation assigned.');
    }

    public function unassign(): void
    {
        $this->selectedConversation?->update(['assigned_operator_id' => null]);

        $this->showAssignPicker = false;
        unset($this->selectedConversation, $this->conversations);

        $this->dispatch('toast', message: 'Conversation unassigned.');
    }

    /** icon, title, message (and optional CTA) shown when a folder has zero conversations. */
    #[Computed]
    public function emptyState(): array
    {
        return match ($this->folder) {
            'mine' => ['icon' => 'bi-folder2-open', 'title' => 'No open conversations assigned to you', 'text' => 'New activity will show up here.'],
            'solved' => ['icon' => 'bi-check2-square', 'title' => 'No solved conversations yet', 'text' => 'New activity will show up here.'],
            'tickets_unassigned' => ['icon' => 'bi-inbox', 'title' => 'No unassigned tickets', 'text' => 'New activity will show up here.'],
            'mentions' => ['icon' => 'bi-at', 'title' => 'You have not been mentioned yet', 'text' => 'New activity will show up here.'],
            'lyro' => ['icon' => 'bi-robot', 'title' => 'No conversations resolved by Lyro yet', 'text' => 'New activity will show up here.'],
            'spam' => ['icon' => 'bi-shield-check', 'title' => 'No spam tickets', 'text' => 'Suspicious emails will appear here.'],
            'view_messenger' => $this->channelEmptyState('messenger', 'bi-messenger', 'Messenger'),
            'view_instagram' => $this->channelEmptyState('instagram', 'bi-instagram', 'Instagram'),
            'view_whatsapp' => $this->channelEmptyState('whatsapp', 'bi-whatsapp', 'WhatsApp'),
            default => ['icon' => 'bi-inbox', 'title' => 'No unassigned conversations', 'text' => 'New activity will show up here.'],
        };
    }

    /**
     * Empty-state for a Views channel. Only shows the "Connect X" CTA when
     * this workspace doesn't already have that channel connected — a
     * connected channel with zero conversations just means nobody has
     * messaged in yet, not that it needs setting up.
     */
    private function channelEmptyState(string $type, string $icon, string $label): array
    {
        $isConnected = Channel::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->where('type', $type)
            ->where('status', 'connected')
            ->exists();

        $state = ['icon' => $icon, 'title' => 'No conversations yet', 'text' => 'New activity will show up here.'];

        if (! $isConnected) {
            $state += ['cta_label' => "Connect {$label}", 'cta_route' => 'app.settings.social', 'cta_param' => $type];
        }

        return $state;
    }

    public function setFolder(string $folder): void
    {
        $valid = ['unassigned', 'mine', 'solved', 'tickets_unassigned', ...self::MORE_FOLDERS, ...array_keys(self::VIEW_CHANNELS)];
        $this->folder = in_array($folder, $valid, true) ? $folder : 'unassigned';

        $this->selectedConversationId = $this->folder === 'mentions'
            ? $this->mentions->first()?->message?->conversation_id
            : $this->conversations->first()?->id;

        $this->showAssignPicker = false;
    }

    /** Views a user bookmarked (their own + anything a teammate shared). */
    #[Computed]
    public function savedViews(): Collection
    {
        return SavedView::where('workspace_id', app('currentWorkspace')->id)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', auth()->id()))
            ->orderBy('name')
            ->get();
    }

    public function startSaveView(): void
    {
        $this->newViewName = '';
        $this->newViewShared = false;
        $this->showSaveView = true;
    }

    public function saveCurrentView(): void
    {
        $this->validate(['newViewName' => 'required|string|max:100']);

        SavedView::create([
            'workspace_id' => app('currentWorkspace')->id,
            'user_id' => $this->newViewShared ? null : auth()->id(),
            'name' => $this->newViewName,
            'filters' => ['folder' => $this->folder],
        ]);

        $this->showSaveView = false;
        unset($this->savedViews);

        $this->dispatch('toast', message: 'View saved.');
    }

    public function applySavedView(int $savedViewId): void
    {
        $view = $this->savedViews->firstWhere('id', $savedViewId);

        if ($view) {
            $this->setFolder($view->filters['folder'] ?? 'unassigned');
        }
    }

    public function deleteSavedView(int $savedViewId): void
    {
        SavedView::where('workspace_id', app('currentWorkspace')->id)->where('id', $savedViewId)->delete();
        unset($this->savedViews);
    }

    /** Open the conversation behind a mention and mark it read. */
    public function selectMention(int $mentionId): void
    {
        $mention = $this->mentions->firstWhere('id', $mentionId);

        if (! $mention) {
            return;
        }

        if (! $mention->read_at) {
            $mention->update(['read_at' => now()]);
            unset($this->mentions);
        }

        $this->selectedConversationId = $mention->message->conversation_id;
        $this->newMessage = '';
        $this->isNote = false;
        $this->showAssignPicker = false;
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $this->newMessage = '';
        $this->uploads = [];
        $this->newUpload = null;
        $this->isNote = false;
        $this->showAssignPicker = false;
    }

    public function toggleNoteMode(): void
    {
        $this->isNote = ! $this->isNote;
    }

    public function sendMessage(): void
    {
        $conversation = $this->selectedConversation;
        $body = trim($this->newMessage);

        if (! $conversation || ($body === '' && $this->uploads === [])) {
            return;
        }

        $attachments = app(AttachmentService::class);

        $this->validate(
            ['newMessage' => ['nullable', 'string', 'max:10000']]
            + $attachments->rules('uploads'),
        );

        $stored = [];

        DB::beginTransaction();

        try {
            $stored = $attachments->store($this->uploads, $conversation->workspace_id, $conversation->id);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => MessageSenderType::Operator,
                'sender_id' => auth()->id(),
                'body' => $body !== '' ? $body : null,
                'attachments' => $stored ?: null,
                'is_private_note' => $this->isNote,
            ]);

            $conversation->update(['last_message_at' => now()]);

            activity('inbox')
                ->performedOn($message)
                ->event('created')
                ->withProperties([
                    'conversation_id' => $conversation->id,
                    'note' => $this->isNote,
                    'attachments' => count($stored),
                ]) // never log the body or file names
                ->log($this->isNote ? 'Internal note added' : 'Operator message sent');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $attachments->discard($stored);

            throw $e;
        }

        $this->createMentions($message);

        unset($this->selectedConversation, $this->conversations);

        $this->newMessage = '';
        $this->uploads = [];
        $this->newUpload = null;
        $this->isNote = false;

        $this->dispatch('message-sent');
    }

    /**
     * Parses "@Full Name" in the message body against active operators in the
     * current workspace, creates a Mention row per match (so it shows up in
     * the Mentions folder), and fires a real database notification — this is
     * the only place Mention rows get created from the product UI itself
     * (previously only the API controller and the demo seeder did).
     */
    private function createMentions(Message $message): void
    {
        if (! preg_match_all('/@([A-Za-z][\w\' -]{1,50})/u', (string) $message->body, $matches)) {
            return;
        }

        $workspace = app('currentWorkspace');
        $operators = $workspace->users()->wherePivot('status', 'active')->get();

        $mentioned = collect($matches[1])
            ->map(fn ($name) => $operators->first(fn ($op) => str_starts_with(
                strtolower($op->name),
                strtolower(trim($name))
            )))
            ->filter()
            ->filter(fn ($op) => $op->id !== auth()->id())
            ->unique('id');

        foreach ($mentioned as $operator) {
            $mention = Mention::create([
                'message_id' => $message->id,
                'mentioned_user_id' => $operator->id,
            ]);

            $operator->notify(new MentionedInConversation($mention));
        }

        if ($mentioned->isNotEmpty()) {
            $this->dispatch('mention-created');
        }
    }

    public function markSolved(): void
    {
        $this->selectedConversation?->update(['status' => ConversationStatus::Solved, 'closed_at' => now()]);
        unset($this->conversations);
    }

    public function render()
    {
        return view('livewire.app.inbox')
            ->layout('layouts.app', ['title' => 'Inbox']);
    }
}
