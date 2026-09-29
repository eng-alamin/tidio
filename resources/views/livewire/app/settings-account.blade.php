<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Account details</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('details')" class="{{ $tab === 'details' ? 'on' : '' }}">Details</a>
            <a href="#" wire:click.prevent="setTab('signature')" class="{{ $tab === 'signature' ? 'on' : '' }}">Signature</a>
            <a href="#" wire:click.prevent="setTab('password')" class="{{ $tab === 'password' ? 'on' : '' }}">Password</a>
        </div>

        @if ($tab === 'details')
            <form wire:submit="save" class="card" style="max-width:520px">
                <label style="margin-top:0">Full name</label>
                <input class="f" wire:model="name">
                @error('name') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Email</label>
                <input class="f" wire:model="email">
                @error('email') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Language</label>
                <select class="f" wire:model="language">
                    <option>English</option>
                    <option>বাংলা</option>
                </select>
                <br>
                <button type="submit" class="btn pri" style="margin-top:18px">Save changes</button>
            </form>
        @elseif ($tab === 'signature')
            <form wire:submit="saveSignature" class="card" style="max-width:560px">
                <label style="margin-top:0">Email signature</label>
                <textarea class="f" rows="5" wire:model="signature"></textarea>
                @error('signature') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                <br>
                <button type="submit" class="btn pri" style="margin-top:18px">Save signature</button>
            </form>
        @elseif ($tab === 'password')
            <form wire:submit="changePassword" class="card" style="max-width:560px">
                <label style="margin-top:0">Current password</label>
                <input class="f" type="password" wire:model="current_password">
                @error('current_password') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>New password</label>
                <input class="f" type="password" wire:model="new_password">
                @error('new_password') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Repeat new password</label>
                <input class="f" type="password" wire:model="new_password_confirmation">
                <br>
                <button type="submit" class="btn pri" style="margin-top:18px">Change password</button>
            </form>
        @endif
    </div>
</div>
