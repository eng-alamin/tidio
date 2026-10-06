<div class="inbox" data-pane="list" wire:poll.5s="$refresh" style="position:relative">
    <nav class="nav" aria-label="Inbox folders">
        <h4>Live conversations</h4>
        <a href="#" wire:click.prevent="setFolder('unassigned')" class="{{ $folder === 'unassigned' ? 'on' : '' }}">
            <i class="bi bi-inbox"></i>Unassigned
        </a>
        <a href="#" wire:click.prevent="setFolder('mine')" class="{{ $folder === 'mine' ? 'on' : '' }}">
            <i class="bi bi-folder2-open"></i>My open
        </a>
        <a href="#" wire:click.prevent="setFolder('solved')" class="{{ $folder === 'solved' ? 'on' : '' }}">
            <i class="bi bi-check2-square"></i>Solved
        </a>
        <h4>Tickets</h4>
        <a href="#" wire:click.prevent="setFolder('tickets_unassigned')" class="{{ $folder === 'tickets_unassigned' ? 'on' : '' }}">
            <i class="bi bi-inbox"></i>Unassigned
        </a>
        <h4>More</h4>
        <a href="#" wire:click.prevent="setFolder('mentions')" class="{{ $folder === 'mentions' ? 'on' : '' }}">
            <i class="bi bi-at"></i>Mentions
        </a>
        <a href="#" wire:click.prevent="setFolder('lyro')" class="{{ $folder === 'lyro' ? 'on' : '' }}">
            <i class="bi bi-robot"></i>Lyro AI Agent
        </a>
        <a href="#" wire:click.prevent="setFolder('spam')" class="{{ $folder === 'spam' ? 'on' : '' }}">
            <i class="bi bi-exclamation-octagon"></i>Spam
        </a>
        <h4>Views</h4>
        <a href="#" wire:click.prevent="setFolder('view_messenger')" class="{{ $folder === 'view_messenger' ? 'on' : '' }}">
            <i class="bi bi-messenger"></i>Messenger
        </a>
        <a href="#" wire:click.prevent="setFolder('view_instagram')" class="{{ $folder === 'view_instagram' ? 'on' : '' }}">
            <i class="bi bi-instagram"></i>Instagram
        </a>
        <a href="#" wire:click.prevent="setFolder('view_whatsapp')" class="{{ $folder === 'view_whatsapp' ? 'on' : '' }}">
            <i class="bi bi-whatsapp"></i>WhatsApp
        </a>

        <h4>Saved <button type="button" class="ib" style="padding:2px" wire:click="startSaveView" aria-label="Save current view"><i class="bi bi-plus-lg"></i></button></h4>
        @forelse ($this->savedViews as $view)
            <a href="#" wire:click.prevent="applySavedView({{ $view->id }})" style="display:flex;align-items:center;justify-content:space-between;gap:6px">
                <span><i class="bi bi-bookmark-star"></i>{{ $view->name }}</span>
                <i class="bi bi-trash" style="opacity:.5" wire:click.stop="deleteSavedView({{ $view->id }})" title="Delete"></i>
            </a>
        @empty
            <small style="color:var(--soft);padding:4px 10px;display:block">No saved views yet.</small>
        @endforelse
    </nav>

    @if ($showSaveView)
        <div class="card" style="position:absolute;left:210px;top:10px;z-index:40;padding:14px;width:260px">
            <label style="margin-top:0;font-size:12px">View name</label>
            <input class="f" wire:model="newViewName" placeholder="e.g. Urgent WhatsApp" autofocus>
            @error('newViewName') <small style="color:var(--bad)">{{ $message }}</small> @enderror
            <label style="display:flex;align-items:center;gap:8px;margin-top:10px;font-size:13px">
                <input type="checkbox" wire:model="newViewShared"> Share with team
            </label>
            <div style="margin-top:12px;display:flex;gap:8px">
                <button type="button" class="btn pri" wire:click="saveCurrentView">Save</button>
                <button type="button" class="btn" wire:click="$set('showSaveView', false)">Cancel</button>
            </div>
        </div>
    @endif

    <div class="list">
        @if (isset(\App\Livewire\App\Inbox::VIEW_CHANNELS[$folder]))
            <div class="t">
                <span class="on">
                    {{ match($folder) { 'view_messenger' => 'Messenger', 'view_instagram' => 'Instagram', 'view_whatsapp' => 'WhatsApp', default => '' } }}
                </span>
            </div>
        @else
            <div class="t">
                <span class="{{ $this->listTab === 'live' ? 'on' : '' }}" style="cursor:pointer" wire:click="setListTab('live')">Live conversations</span>
                <a href="#" wire:click.prevent="setListTab('tickets')" class="{{ $this->listTab === 'tickets' ? 'on' : '' }}"><span>Tickets</span></a>
            </div>
        @endif

        @if ($folder === 'mentions')
            @forelse ($this->mentions as $mention)
                <div wire:key="mention-{{ $mention->id }}"
                     class="conv {{ $mention->message->conversation_id === $selectedConversationId ? 'on' : '' }}"
                     wire:click="selectMention({{ $mention->id }})">
                    <b>
                        {{ $mention->message->conversation->displayName() }}
                        @unless ($mention->read_at) <span class="pill ok" style="margin-left:6px">New</span> @endunless
                    </b>
                    <small>{{ \Illuminate\Support\Str::limit($mention->message->body, 60) }}</small>
                </div>
            @empty
                <div class="conv" style="cursor:default"><small style="color:var(--soft)">No mentions yet.</small></div>
            @endforelse
        @else
            @forelse ($this->conversations as $conversation)
                <div wire:key="conv-{{ $conversation->id }}"
                     class="conv {{ $conversation->id === $selectedConversationId ? 'on' : '' }}"
                     wire:click="selectConversation({{ $conversation->id }})">
                    <b>{{ $conversation->displayName() }}</b>
                    <small>{{ $conversation->messages->first()?->body ?? 'No messages yet' }}</small>
                </div>
            @empty
                <div class="conv" style="cursor:default"><small style="color:var(--soft)">No conversations here.</small></div>
            @endforelse
        @endif
    </div>

    @if ($this->selectedConversation)
        @php($conversation = $this->selectedConversation)
        <div class="chat">
            <header>
                <b>{{ $conversation->displayName() }}</b>
                <span style="display:flex;gap:8px;align-items:center">
                <span style="position:relative">
                    <button class="btn" wire:click="toggleAssignPicker">
                        {{ $conversation->assignedOperator?->name ?? 'Unassigned' }}
                        <i class="bi bi-chevron-down" style="font-size:11px;margin-left:4px"></i>
                    </button>
                    @if ($showAssignPicker)
                        <div class="card" style="position:absolute;right:0;top:calc(100% + 6px);z-index:20;min-width:200px;padding:6px">
                            @if ($conversation->assigned_operator_id)
                                <button type="button" class="btn" style="width:100%;justify-content:flex-start;margin-bottom:4px" wire:click="unassign">
                                    <i class="bi bi-x-circle" style="margin-right:6px"></i>Unassign
                                </button>
                            @endif
                            @forelse ($this->operators as $operator)
                                <button type="button" class="btn" style="width:100%;justify-content:flex-start;background:{{ $operator->id === $conversation->assigned_operator_id ? 'var(--paper2, #1a2134)' : 'transparent' }}"
                                        wire:click="assignOperator({{ $operator->id }})">
                                    {{ $operator->name }}
                                </button>
                            @empty
                                <p style="color:var(--soft);padding:6px 10px;margin:0">No operators in this workspace.</p>
                            @endforelse
                        </div>
                    @endif
                </span>
                    @if ($folder === 'lyro' && $conversation->aiMeta?->resolved_by_ai)
                        <span class="pill ok">Resolved by Lyro</span>
                    @endif
                    @if ($conversation->status->value !== 'solved')
                        <button class="btn pri" wire:click="markSolved">Mark solved</button>
                    @endif
                </span>
            </header>

            <div class="msgs" id="msgs-{{ $conversation->id }}">
                @forelse ($conversation->messages as $message)
                    <div class="m {{ $message->sender_type->value === 'operator' ? 'me' : '' }} {{ $message->is_private_note ? 'note' : '' }}"
                         wire:key="msg-{{ $message->id }}">
                        {{ $message->is_private_note ? 'Note: ' : '' }}{{ $message->body }}
                    </div>
                @empty
                    <div class="m" style="color:var(--soft)">No messages yet.</div>
                @endforelse
            </div>

            <form class="reply" wire:submit="sendMessage">
                <input type="text" wire:model="newMessage"
                       placeholder="{{ $isNote ? 'Write an internal note…' : 'Type a message…' }}"
                       aria-label="Message">
                <button type="button" class="btn {{ $isNote ? 'pri' : '' }}" wire:click="toggleNoteMode">Add internal note</button>
                <button type="submit" class="btn pri">Send</button>
            </form>
        </div>

        <div class="info">
            <h4>Customer data</h4>
            <dl>
                <dt>Name</dt><dd>{{ $conversation->displayName() }}</dd>
                <dt>Location</dt><dd>{{ $conversation->visitor?->location ?? '—' }}</dd>
                <dt>Browser</dt><dd>{{ $conversation->visitor?->browser ?? '—' }}</dd>
                <dt>Email</dt><dd>{{ $conversation->contact?->email ?? '—' }}</dd>
                <dt>Phone</dt><dd>{{ $conversation->contact?->phone ?? '—' }}</dd>
            </dl>
            @if ($conversation->aiMeta?->resolved_by_ai)
                <h4>Handled by AI</h4>
                <dl>
                    <dt>Handled by</dt><dd>Lyro</dd>
                    <dt>Confidence</dt><dd>{{ $conversation->aiMeta->confidence_score !== null ? number_format($conversation->aiMeta->confidence_score * 100).'%' : '—' }}</dd>
                </dl>
            @endif
            <h4>Tickets history</h4>
            <p style="color:var(--soft)">No ticket history</p>
            <h4>Customer notes</h4>
            <p style="color:var(--soft)">No customer notes</p>
        </div>
    @elseif (($folder === 'mentions' ? $this->mentions : $this->conversations)->isEmpty())
        @php($empty = $this->emptyState)
        <div class="chat">
            <div class="empty" role="status">
                <span class="empty-ic"><i class="bi {{ $empty['icon'] }}" aria-hidden="true"></i></span>
                <h3>{{ $empty['title'] }}</h3>
                <p>{{ $empty['text'] }}</p>
                @isset($empty['cta_label'])
                    <a class="btn pri" href="{{ route($empty['cta_route'], $empty['cta_param']) }}">{{ $empty['cta_label'] }}</a>
                @endisset
            </div>
        </div>
    @else
        <div class="chat"><div class="msgs" style="color:var(--soft);padding:16px">Select a conversation to start.</div></div>
    @endif
</div>

@script
<script>
    $wire.on('message-sent', () => {
        const el = document.querySelector('.msgs');
        if (el) el.scrollTop = el.scrollHeight;
    });
</script>
@endscript
