<div>
  <div class="page-head">
    <div><h2>Feature Flags</h2><p>Switch features on or off for everyone, or for single tenants</p></div>
    @if ($this->canManage())
      <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>New flag</button>
    @endif
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Flags</div><div class="value">{{ number_format($this->stats['total']) }}</div></div>
    <div class="glass stat-card"><div class="label">On for everyone</div><div class="value">{{ number_format($this->stats['on']) }}</div></div>
    <div class="glass stat-card"><div class="label">Off for everyone</div><div class="value">{{ number_format($this->stats['off']) }}</div></div>
    <div class="glass stat-card"><div class="label">Tenant overrides</div><div class="value">{{ number_format($this->stats['overrides']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="flagSearch">Search flags</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search by key…" id="flagSearch" wire:model.live.debounce.300ms="search">
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Flag</th><th scope="col">Overrides</th><th scope="col">Global setting</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->flags as $flag)
            <tr wire:key="flag-{{ $flag->id }}">
              <td data-label="Flag" class="cell-main-td"><code style="font-size:.82rem;">{{ $flag->key }}</code></td>
              <td data-label="Overrides">
                @if ($flag->overrides_on + $flag->overrides_off === 0)
                  <span style="color:var(--text-soft);">None</span>
                @else
                  {{ $flag->overrides_on }} on, {{ $flag->overrides_off }} off
                @endif
              </td>
              <td data-label="Global setting">
                @if ($this->canManage())
                  <button class="toggle-switch {{ $flag->is_enabled ? 'on' : '' }}" type="button" role="switch"
                          aria-checked="{{ $flag->is_enabled ? 'true' : 'false' }}" aria-label="Turn {{ $flag->key }} {{ $flag->is_enabled ? 'off' : 'on' }} for everyone"
                          wire:click="toggleGlobal({{ $flag->id }})" wire:loading.attr="disabled" wire:target="toggleGlobal({{ $flag->id }})"></button>
                @else
                  <span class="status-pill {{ $flag->is_enabled ? 'status-active' : 'status-suspended' }}">{{ $flag->is_enabled ? 'On' : 'Off' }}</span>
                @endif
              </td>
              <td data-label="Actions">
                <div class="row-actions">
                  <button class="icon-action" type="button" wire:click="openOverrides({{ $flag->id }})" aria-label="{{ $this->canManage() ? 'Manage' : 'View' }} tenant overrides for {{ $flag->key }}"><i class="bi bi-buildings"></i></button>
                  @if ($this->canManage())
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $flag->id }})" aria-label="Delete {{ $flag->key }}"><i class="bi bi-trash3"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-toggles"></i></div>
                  <h6>No flags found</h6>
                  <p>{{ $this->canManage() ? 'Try a different search, or create a new flag.' : 'Try a different search.' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->flags->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="flag-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'create')
          <h4>New feature flag</h4>
          <div class="modal-sub">The flag starts switched off. Your code checks it by this key, and the key can't be changed later.</div>
          <form wire:submit="create">
            <div class="form-field">
              <label class="form-label" for="f-fkey">Flag key</label>
              <input class="form-control @error('newKey') is-invalid @enderror" id="f-fkey" wire:model="newKey" placeholder="e.g. new_inbox_layout" maxlength="100" autocomplete="off" autofocus>
              @error('newKey')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>
            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="create">Create flag</button>
            </div>
          </form>

        @elseif ($modal === 'overrides' && $this->selected)
          <h4>Tenant overrides</h4>
          <div class="modal-sub"><code>{{ $this->selected->key }}</code> is <strong>{{ $this->selected->is_enabled ? 'on' : 'off' }}</strong> by default. An override replaces that for one tenant.</div>

          @forelse ($this->overrides as $override)
            <div class="detail-row" wire:key="override-{{ $override->id }}" style="align-items:center;">
              <span>
                {{ $override->workspace?->name ?? 'Unknown tenant' }}
                @if ($override->workspace?->trashed()) <span style="color:var(--text-soft);">(deleted)</span> @endif
              </span>
              <span class="d-flex align-items-center gap-2">
                @if ($this->canManage())
                  <button class="toggle-switch {{ $override->is_enabled ? 'on' : '' }}" type="button" role="switch"
                          aria-checked="{{ $override->is_enabled ? 'true' : 'false' }}"
                          aria-label="Override for {{ $override->workspace?->name ?? 'tenant' }}: {{ $override->is_enabled ? 'on' : 'off' }}"
                          wire:click="flipOverride({{ $override->id }})" wire:loading.attr="disabled" wire:target="flipOverride({{ $override->id }})"></button>
                  <button class="icon-action danger" type="button" wire:click="removeOverride({{ $override->id }})" aria-label="Remove override for {{ $override->workspace?->name ?? 'tenant' }}"><i class="bi bi-x-lg"></i></button>
                @else
                  <span class="status-pill {{ $override->is_enabled ? 'status-active' : 'status-suspended' }}">{{ $override->is_enabled ? 'On' : 'Off' }}</span>
                @endif
              </span>
            </div>
          @empty
            <div class="state-block" style="padding:18px 0;">
              <p>No overrides. Every tenant follows the default.</p>
            </div>
          @endforelse

          @if ($this->canManage())
            <div class="form-field" style="margin-top:18px;">
              <label class="form-label" for="f-otenant">Add a tenant override</label>

              @if ($this->chosenTenant)
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <span class="status-pill status-trial">{{ $this->chosenTenant->name }}</span>
                  <button class="btn btn-ghost" type="button" wire:click="clearTenant">Change</button>
                  <select class="form-select" style="max-width:110px;" aria-label="Override state" wire:model="overrideState">
                    <option value="on">On</option>
                    <option value="off">Off</option>
                  </select>
                  <button class="btn btn-cobalt" type="button" wire:click="addOverride" wire:loading.attr="disabled" wire:target="addOverride">Add override</button>
                </div>
              @else
                <input class="form-control" id="f-otenant" wire:model.live.debounce.300ms="tenantSearch" placeholder="Type at least 2 letters of the tenant name…" autocomplete="off">
                @if (mb_strlen(trim($tenantSearch)) >= 2)
                  <div style="margin-top:8px;">
                    @forelse ($this->tenantMatches as $tenant)
                      <button class="dd-item" type="button" wire:key="match-{{ $tenant->id }}" wire:click="chooseTenant({{ $tenant->id }})" style="width:100%; text-align:left;"><i class="bi bi-buildings" aria-hidden="true"></i><span class="t">{{ $tenant->name }}</span></button>
                    @empty
                      <div class="modal-sub" style="margin:6px 0 0;">No matching tenant without an override.</div>
                    @endforelse
                  </div>
                @endif
              @endif
            </div>
          @endif

          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Close</button>
          </div>

        @elseif ($modal === 'delete' && $this->selected)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete {{ $this->selected->key }}?</h4>
          <div class="modal-sub">The flag and all of its tenant overrides are removed. Any code still checking it falls back to off.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep flag</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete flag</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
