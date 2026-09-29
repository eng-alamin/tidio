<div class="body">
    
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Live chat · Appearance</h2>

        <form wire:submit="save" class="two">
            <div class="card">
                <label style="margin-top:0">Header title</label>
                <input class="f" wire:model.live="header">
                @error('header') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Welcome message</label>
                <input class="f" wire:model.live="welcome_message">
                @error('welcome_message') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Widget color</label>
                <div class="sw">
                    @foreach ($swatches as $swatch)
                        <i wire:click="$set('background_color', '{{ $swatch }}')"
                           style="background:{{ $swatch }};cursor:pointer;{{ $background_color === $swatch ? 'outline:2px solid var(--ink);outline-offset:2px' : '' }}"></i>
                    @endforeach
                </div>
                @error('background_color') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Position</label>
                <select class="f" wire:model="position">
                    <option value="right">Right</option>
                    <option value="left">Left</option>
                </select>
                @error('position') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <br><button type="submit" class="btn pri" style="margin-top:18px">Save changes</button>
            </div>

            <div class="card">
                <label style="margin-top:0">Preview</label>
                <div style="border:1px solid var(--line);border-radius:12px;padding:16px;background:var(--paper2, #f7f8fb)">
                    <div style="background:{{ $background_color }};color:#fff;border-radius:10px 10px 0 0;padding:10px 14px;font-weight:600">
                        {{ $header }}
                    </div>
                    <div style="padding:14px;font-size:14px;color:var(--ink)">
                        {{ $welcome_message }}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
