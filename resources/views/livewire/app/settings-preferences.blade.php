<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Preferences</h2>

        <form wire:submit="save" class="card" style="max-width:520px">
            <label style="margin-top:0">Timezone</label>
            <select class="f" wire:model="timezone">
                @foreach (['Asia/Dhaka', 'UTC', 'America/New_York', 'Europe/London', 'Asia/Kolkata'] as $tz)
                    <option value="{{ $tz }}">{{ $tz }}</option>
                @endforeach
            </select>
            @error('timezone') <small style="color:var(--bad)">{{ $message }}</small> @enderror

            <label>Date format</label>
            <select class="f" wire:model="date_format">
                <option value="M j, Y">Jan 5, 2026</option>
                <option value="d/m/Y">05/01/2026</option>
                <option value="Y-m-d">2026-01-05</option>
            </select>

            <label style="display:flex;align-items:center;gap:8px;margin-top:14px">
                <input type="checkbox" wire:model="sound_enabled"> Play a sound on new message
            </label>

            <button type="submit" class="btn pri" style="margin-top:18px">Save preferences</button>
        </form>
    </div>
</div>
