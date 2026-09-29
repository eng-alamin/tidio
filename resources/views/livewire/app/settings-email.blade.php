<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Email</h2>

        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('mailbox')" class="{{ $tab === 'mailbox' ? 'on' : '' }}">Mailbox</a>
            <a href="#" wire:click.prevent="setTab('sender')" class="{{ $tab === 'sender' ? 'on' : '' }}">Sender address</a>
            <a href="#" wire:click.prevent="setTab('blocked')" class="{{ $tab === 'blocked' ? 'on' : '' }}">Blocked addresses</a>
            <a href="#" wire:click.prevent="setTab('domains')" class="{{ $tab === 'domains' ? 'on' : '' }}">Domains</a>
        </div>

        @if ($tab === 'mailbox')
            <form wire:submit="connectMailbox" class="card" style="max-width:560px">
                <p style="color:var(--soft);margin-top:0">Receive customer emails as tickets.</p>
                <label>Support address</label>
                <input class="f" wire:model="support_address" placeholder="support@yourcompany.com">
                @error('support_address') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                <label>Sender name</label>
                <input class="f" wire:model="sender_name">
                @error('sender_name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                <br><button type="submit" class="btn pri" style="margin-top:18px">Connect mailbox</button>
            </form>
        @endif

        @if ($tab === 'sender')
            <form wire:submit="saveSender" class="card" style="max-width:560px">
                <label style="margin-top:0">Sender name</label>
                <input class="f" wire:model="sender_name">
                @error('sender_name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                <label>Sender address</label>
                <input class="f" wire:model="sender_address">
                @error('sender_address') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                <label>Reply-to</label>
                <input class="f" wire:model="reply_to" placeholder="Optional">
                @error('reply_to') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                <br><button type="submit" class="btn pri" style="margin-top:18px">Save</button>
            </form>
        @endif

        @if ($tab === 'blocked')
            <form wire:submit="blockAddress" class="tools">
                <input class="f" wire:model="block_address" placeholder="name@example.com">
                <button type="submit" class="btn pri">Block</button>
            </form>
            @error('block_address') <small style="color:var(--bad)">{{ $message }}</small> @enderror

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Address</th><th>Blocked on</th><th></th></tr>
                    @forelse ($this->blockedAddresses as $blocked)
                        <tr>
                            <td>{{ $blocked->address }}</td>
                            <td>{{ $blocked->blocked_at->format('M j') }}</td>
                            <td><button type="button" class="btn" wire:click="unblock({{ $blocked->id }})">Unblock</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--soft)">No blocked addresses yet.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif

        @if ($tab === 'domains')
            <form wire:submit="addDomain" class="tools">
                <span style="flex:1"></span>
                <input class="f" wire:model="new_domain" placeholder="yourcompany.com" style="max-width:240px">
                <button type="submit" class="btn pri">+ Add domain</button>
            </form>
            @error('new_domain') <small style="color:var(--bad)">{{ $message }}</small> @enderror

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Domain</th><th>SPF</th><th>DKIM</th><th>Status</th></tr>
                    @forelse ($this->domains as $domain)
                        <tr>
                            <td>{{ $domain->domain }}</td>
                            <td><span class="pill {{ $domain->spf_status === 'ok' ? 'ok' : '' }}">{{ ucfirst($domain->spf_status) }}</span></td>
                            <td><span class="pill {{ $domain->dkim_status === 'ok' ? 'ok' : '' }}">{{ ucfirst($domain->dkim_status) }}</span></td>
                            <td><span class="pill">{{ ucfirst($domain->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No domains added yet.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif
    </div>
</div>
