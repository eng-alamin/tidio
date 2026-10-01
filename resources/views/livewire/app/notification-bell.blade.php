<div style="position:relative">
    <button class="ib" wire:click="toggle" aria-label="Notifications" aria-haspopup="true" @if($open) aria-expanded="true" @else aria-expanded="false" @endif>
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if ($this->unreadCount > 0)
            <span style="position:absolute;top:2px;right:2px;background:var(--bad);color:#fff;border-radius:999px;font-size:10px;line-height:1;padding:2px 5px;min-width:15px;text-align:center">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div wire:click.outside="toggle" class="card"
             style="position:absolute;right:0;top:calc(100% + 8px);width:320px;max-height:400px;overflow:auto;z-index:50;padding:0;box-shadow:var(--shadow-lg,0 12px 32px rgba(0,0,0,.25))">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid var(--line)">
                <b style="font-size:14px">Notifications</b>
                @if ($this->unreadCount > 0)
                    <button type="button" class="btn" style="padding:2px 8px;font-size:12px" wire:click="markAllRead">Mark all read</button>
                @endif
            </div>

            @forelse ($this->notifications as $notification)
                <a href="{{ route('app.inbox') }}"
                   wire:key="notif-{{ $notification->id }}"
                   wire:click="markAsRead('{{ $notification->id }}')"
                   style="display:block;padding:10px 14px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;{{ $notification->read_at ? 'opacity:.55' : '' }}">
                    <div style="font-size:13px">
                        <b>{{ $notification->data['by'] ?? 'Someone' }}</b> mentioned you
                    </div>
                    @if (!empty($notification->data['excerpt']))
                        <div style="font-size:12px;color:var(--soft);margin-top:2px">{{ $notification->data['excerpt'] }}</div>
                    @endif
                    <div style="font-size:11px;color:var(--soft);margin-top:4px">{{ $notification->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div style="padding:20px 14px;color:var(--soft);font-size:13px;text-align:center">You're all caught up.</div>
            @endforelse
        </div>
    @endif
</div>
