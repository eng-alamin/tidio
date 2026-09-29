<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">SLA policies</h2>

        <form wire:submit="save" class="card" style="max-width:560px">
            <label style="margin-top:0">First response target</label>
            <select class="f" wire:model="first_response_minutes">
                <option value="15">15 minutes</option>
                <option value="60">1 hour</option>
                <option value="240">4 hours</option>
            </select>
            @error('first_response_minutes') <small style="color:var(--bad)">{{ $message }}</small> @enderror

            <label>Resolution target</label>
            <select class="f" wire:model="resolution_minutes">
                <option value="1440">24 hours</option>
                <option value="2880">48 hours</option>
            </select>
            @error('resolution_minutes') <small style="color:var(--bad)">{{ $message }}</small> @enderror

            <label>Applies to</label>
            <select class="f" wire:model="applies_to">
                <option value="ticket">Tickets</option>
                <option value="chat">Live conversations</option>
                <option value="all">All conversations</option>
            </select>
            @error('applies_to') <small style="color:var(--bad)">{{ $message }}</small> @enderror
            <br>
            <button type="submit" class="btn pri" style="margin-top:18px">Save policy</button>
        </form>
    </div>
</div>
