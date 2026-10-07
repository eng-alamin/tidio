@php
  $initials = collect(explode(' ', trim($admin->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
  $roleLabel = ['super_admin' => 'Super Admin', 'support_staff' => 'Support Staff', 'billing_admin' => 'Billing Admin'][$admin->role->value] ?? 'Staff';
  $device = $this->currentDevice();
@endphp
<div>
  <div class="page-head"><div><h2>My Profile</h2><p>Your personal platform staff account</p></div></div>

  <div class="grid-2">
    <div class="glass panel mb-3">
      <div class="panel-title"><h5>Profile details</h5></div>
      <div class="d-flex align-items-center gap-3 mb-4">
        <span class="avatar" aria-hidden="true" style="width:56px; height:56px; font-size:1.15rem;">{{ $initials }}</span>
        <div>
          <div style="font-weight:600;">{{ $admin->name }}</div>
          <div style="color:var(--text-soft); font-size:.8rem;">{{ $roleLabel }}</div>
        </div>
      </div>
      <form wire:submit="saveProfile">
        <div class="form-field">
          <label class="form-label" for="p-name">Full name</label>
          <input class="form-control @error('name') is-invalid @enderror" id="p-name" wire:model="name" maxlength="120" autocomplete="name">
          @error('name')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="p-email">Email address</label>
          <input class="form-control @error('email') is-invalid @enderror" id="p-email" type="email" wire:model="email" maxlength="190" autocomplete="email">
          <div style="color:var(--text-soft); font-size:.76rem; margin-top:5px;">This is also your sign-in email.</div>
          @error('email')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="p-phone">Phone number (optional)</label>
          <input class="form-control @error('phone') is-invalid @enderror" id="p-phone" wire:model="phone" maxlength="32" autocomplete="tel">
          @error('phone')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="p-tz">Timezone (optional)</label>
          <select class="form-select @error('timezone') is-invalid @enderror" id="p-tz" wire:model="timezone">
            <option value="">Not set</option>
            @foreach ($this->timezones() as $tz)
              <option value="{{ $tz }}">{{ $tz }}</option>
            @endforeach
          </select>
          @error('timezone')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <button class="btn btn-cobalt" type="submit" wire:loading.attr="disabled" wire:target="saveProfile">Save changes</button>
      </form>
    </div>

    <div>
      <div class="glass panel mb-3">
        <div class="panel-title"><h5>Password</h5></div>
        <form wire:submit="changePassword">
          <div class="form-field">
            <label class="form-label" for="p-current">Current password</label>
            <input class="form-control @error('currentPassword') is-invalid @enderror" id="p-current" type="password" wire:model="currentPassword" autocomplete="current-password">
            @error('currentPassword')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
          </div>
          <div class="form-field">
            <label class="form-label" for="p-new">New password</label>
            <input class="form-control @error('newPassword') is-invalid @enderror" id="p-new" type="password" wire:model="newPassword" autocomplete="new-password">
            <div style="color:var(--text-soft); font-size:.76rem; margin-top:5px;">At least 10 characters with upper and lower case letters and a number.</div>
            @error('newPassword')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
          </div>
          <div class="form-field">
            <label class="form-label" for="p-confirm">Confirm new password</label>
            <input class="form-control @error('newPasswordConfirmation') is-invalid @enderror" id="p-confirm" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password">
            @error('newPasswordConfirmation')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
          </div>
          <button class="btn btn-cobalt" type="submit" wire:loading.attr="disabled" wire:target="changePassword">Update password</button>
        </form>
      </div>

      <div class="glass panel mb-3">
        <div class="panel-title"><h5>This device</h5></div>
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div style="font-weight:600; font-size:.88rem;">{{ $device['browser'] }} on {{ $device['os'] }}</div>
            <div style="color:var(--text-soft); font-size:.78rem;">{{ $device['ip'] ?? 'Unknown IP' }} · signed in now</div>
          </div>
          <span style="background:rgba(46,196,120,.14); color:#2ec478; font-size:.72rem; font-weight:600; padding:4px 10px; border-radius:99px;">Current session</span>
        </div>
      </div>
    </div>
  </div>
</div>
