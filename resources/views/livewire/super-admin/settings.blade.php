<div>
  <div class="page-head"><div><h2>Platform Settings</h2><p>Global configuration for this Loop instance</p></div></div>

  <div class="grid-2">
    <div class="glass panel mb-3">
      <div class="panel-title"><h5>General</h5></div>
      <form wire:submit="saveGeneral">
        <div class="form-field">
          <label class="form-label" for="s-name">Platform name</label>
          <input class="form-control @error('platformName') is-invalid @enderror" id="s-name" wire:model="platformName" maxlength="60" autocomplete="off">
          @error('platformName')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="s-email">Support email</label>
          <input class="form-control @error('supportEmail') is-invalid @enderror" id="s-email" type="email" wire:model="supportEmail" maxlength="190" autocomplete="off">
          @error('supportEmail')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="s-tz">Default timezone for new tenants</label>
          <select class="form-select @error('defaultTimezone') is-invalid @enderror" id="s-tz" wire:model="defaultTimezone">
            @foreach ($this->timezones() as $tz)
              <option value="{{ $tz }}">{{ $tz }}</option>
            @endforeach
          </select>
          @error('defaultTimezone')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="s-trial">Free trial length (days)</label>
          <input class="form-control @error('trialDays') is-invalid @enderror" id="s-trial" type="number" min="1" max="90" wire:model="trialDays">
          <div style="color:var(--text-soft); font-size:.76rem; margin-top:5px;">Applies to tenants created from now on.</div>
          @error('trialDays')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
        <button class="btn btn-cobalt" type="submit" wire:loading.attr="disabled" wire:target="saveGeneral">Save changes</button>
      </form>
    </div>

    <div class="glass panel mb-3">
      <div class="panel-title"><h5>Access &amp; Maintenance</h5></div>
      <form wire:submit="saveAccess">
        <div class="form-field">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="s-imp" wire:model="allowImpersonation">
            <label class="form-check-label" for="s-imp">Allow tenant impersonation</label>
          </div>
          <div style="color:var(--text-soft); font-size:.78rem; margin-top:4px;">Lets platform staff sign in as a tenant user for support. Sessions already running are not cut off.</div>
        </div>

        <div class="form-field">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="s-maint" wire:model.live="maintenanceMode">
            <label class="form-check-label" for="s-maint">Maintenance mode</label>
          </div>
          <div style="color:var(--text-soft); font-size:.78rem; margin-top:4px;">Blocks the tenant app panel with a "service unavailable" page. The public chat widget keeps working.</div>
        </div>

        @if ($maintenanceMode)
          <div class="alert alert-danger" role="status" style="margin-bottom:16px;">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <span>Tenants will be locked out as soon as you save. Staff who are impersonating a tenant can still get in.</span>
          </div>
        @endif

        <div class="form-field">
          <label class="form-label" for="s-msg">Maintenance message</label>
          <textarea class="form-control @error('maintenanceMessage') is-invalid @enderror" id="s-msg" rows="3" maxlength="255" wire:model="maintenanceMessage"></textarea>
          @error('maintenanceMessage')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>

        <button class="btn btn-cobalt" type="submit" wire:loading.attr="disabled" wire:target="saveAccess">Save changes</button>
      </form>
    </div>
  </div>
</div>
