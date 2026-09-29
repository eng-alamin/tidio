<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        @php($label = ucfirst($type))
        <h2 style="font-size:22px;margin-bottom:14px">{{ $label }}</h2>

        @if ($this->channel && $this->channel->status === 'connected')
            <div class="card" style="max-width:520px">
                <p><i class="bi bi-check-circle-fill" style="color:var(--ok)"></i> Connected
                    @if(($this->channel->credentials['handle'] ?? null)) as <b>{{ $this->channel->credentials['handle'] }}</b>@endif
                </p>
                <p style="color:var(--soft)">Since {{ $this->channel->connected_at?->diffForHumans() }}</p>
                <button class="btn" wire:click="disconnect" wire:confirm="Disconnect {{ $label }}?">Disconnect</button>
            </div>
        @else
            <div class="card" style="max-width:520px">
                <p style="color:var(--soft)">Connect your {{ $label }} account to receive and reply to messages here.</p>
                @if ($showConnect)
                    <form wire:submit="connect">
                        <label>{{ $label }} page / account handle</label>
                        <input class="f" wire:model="handle" placeholder="@yourbusiness">
                        @error('handle') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        <br>
                        <button type="submit" class="btn pri" style="margin-top:12px">Connect</button>
                        <button type="button" class="btn" wire:click="$set('showConnect', false)">Cancel</button>
                    </form>
                @else
                    <button class="btn pri" wire:click="$set('showConnect', true)">Connect {{ $label }}</button>
                @endif
            </div>
        @endif
    </div>
</div>
