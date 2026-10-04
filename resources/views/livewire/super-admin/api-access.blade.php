<div>
  <div class="page-head">
    <div><h2>API Keys &amp; Webhooks</h2><p>Every tenant's API key and webhook endpoint, in one place</p></div>
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Tenant API keys</div><div class="value">{{ number_format($this->stats['keys']) }}</div></div>
    <div class="glass stat-card"><div class="label">Active webhooks</div><div class="value">{{ number_format($this->stats['active']) }}</div></div>
    <div class="glass stat-card"><div class="label">Paused webhooks</div><div class="value">{{ number_format($this->stats['paused']) }}</div></div>
    <div class="glass stat-card"><div class="label">Tenants using webhooks</div><div class="value">{{ number_format($this->stats['tenants']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
      <button class="btn btn-sm {{ $tab === 'keys' ? 'btn-cobalt' : 'btn-ghost' }}" type="button" wire:click="setTab('keys')" aria-pressed="{{ $tab === 'keys' ? 'true' : 'false' }}"><i class="bi bi-key-fill" aria-hidden="true"></i>API keys</button>
      <button class="btn btn-sm {{ $tab === 'webhooks' ? 'btn-cobalt' : 'btn-ghost' }}" type="button" wire:click="setTab('webhooks')" aria-pressed="{{ $tab === 'webhooks' ? 'true' : 'false' }}"><i class="bi bi-plug-fill" aria-hidden="true"></i>Webhooks</button>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="apiSearch">{{ $tab === 'keys' ? 'Search tenants' : 'Search webhooks' }}</label>
      <input class="form-control" style="max-width:280px;" id="apiSearch" wire:model.live.debounce.300ms="search"
             placeholder="{{ $tab === 'keys' ? 'Search by tenant name or slug…' : 'Search by tenant or endpoint…' }}">

      @if ($tab === 'webhooks')
        <select class="form-select" style="max-width:170px;" aria-label="Filter by status" wire:model.live="statusFilter">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="paused">Paused</option>
        </select>
      @endif
    </div>

    @if ($tab === 'keys')
      <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,setTab,gotoPage,previousPage,nextPage">
        <table class="responsive-table">
          <thead>
            <tr>
              <th scope="col">Tenant</th><th scope="col">API key</th><th scope="col">Webhooks</th><th scope="col">Tenant status</th>
              <th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($this->keys as $workspace)
              @php($status = $this->tenantStatus($workspace))
              <tr wire:key="key-{{ $workspace->id }}">
                <td data-label="Tenant" class="cell-main-td">{{ $workspace->name }}<div style="color:var(--text-soft); font-size:.76rem;">{{ $workspace->slug }}</div></td>
                <td data-label="API key"><code style="font-size:.8rem;">{{ $this->maskedKey($workspace->api_key) }}</code></td>
                <td data-label="Webhooks">{{ number_format($workspace->webhooks_count) }}</td>
                <td data-label="Tenant status"><span class="status-pill {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                <td data-label="Actions">
                  <div class="row-actions">
                    @if ($this->canManage())
                      <button class="icon-action danger" type="button" wire:click="confirmRotate({{ $workspace->id }})" aria-label="Rotate API key for {{ $workspace->name }}"><i class="bi bi-arrow-repeat"></i></button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="padding:0;">
                  <div class="state-block">
                    <div class="state-icon" aria-hidden="true"><i class="bi bi-key"></i></div>
                    <h6>No API keys found</h6>
                    <p>Try a different search.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">{{ $this->keys->links() }}</div>
      <p class="mt-3 mb-0" style="color:var(--text-soft); font-size:.8rem;">Full keys are never shown here. After a rotation, the tenant finds the new key under Settings &rarr; Developer.</p>
    @else
      <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,setTab,gotoPage,previousPage,nextPage">
        <table class="responsive-table">
          <thead>
            <tr>
              <th scope="col">Tenant</th><th scope="col">Endpoint</th><th scope="col">Events</th><th scope="col">Status</th>
              <th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($this->webhooks as $webhook)
              @php($events = (array) $webhook->events)
              <tr wire:key="hook-{{ $webhook->id }}">
                <td data-label="Tenant" class="cell-main-td">
                  {{ $webhook->workspace?->name ?? 'Unknown tenant' }}
                  @if ($webhook->workspace?->is_suspended) <span class="status-pill status-suspended" style="margin-left:6px;">Suspended</span> @endif
                </td>
                <td data-label="Endpoint"><code style="font-size:.8rem; word-break:break-all;" title="{{ $this->endpoint($webhook->url) }}">{{ \Illuminate\Support\Str::limit($this->endpoint($webhook->url), 60) }}</code></td>
                <td data-label="Events">
                  @if (count($events) === 0)
                    <span style="color:var(--text-soft);">None</span>
                  @else
                    <code style="font-size:.78rem;">{{ $events[0] }}</code>@if (count($events) > 1) <span style="color:var(--text-soft);">+{{ count($events) - 1 }} more</span>@endif
                  @endif
                </td>
                <td data-label="Status">
                  @if ($this->canManage())
                    <button class="toggle-switch {{ $webhook->is_active ? 'on' : '' }}" type="button" role="switch"
                            aria-checked="{{ $webhook->is_active ? 'true' : 'false' }}"
                            aria-label="{{ $webhook->is_active ? 'Pause' : 'Resume' }} webhook for {{ $webhook->workspace?->name ?? 'tenant' }}"
                            wire:click="toggleWebhook({{ $webhook->id }})" wire:loading.attr="disabled" wire:target="toggleWebhook({{ $webhook->id }})"></button>
                  @else
                    <span class="status-pill {{ $webhook->is_active ? 'status-active' : 'status-suspended' }}">{{ $webhook->is_active ? 'Active' : 'Paused' }}</span>
                  @endif
                </td>
                <td data-label="Actions">
                  <div class="row-actions">
                    @if ($this->canManage())
                      <button class="icon-action danger" type="button" wire:click="confirmDeleteWebhook({{ $webhook->id }})" aria-label="Delete webhook for {{ $webhook->workspace?->name ?? 'tenant' }}"><i class="bi bi-trash3"></i></button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="padding:0;">
                  <div class="state-block">
                    <div class="state-icon" aria-hidden="true"><i class="bi bi-plug"></i></div>
                    <h6>No webhooks found</h6>
                    <p>Try a different search or filter.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">{{ $this->webhooks->links() }}</div>
      <p class="mt-3 mb-0" style="color:var(--text-soft); font-size:.8rem;">Query strings are hidden from endpoints because they can hold secrets. Secrets are never shown.</p>
    @endif
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="api-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'rotate' && $this->selectedWorkspace)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Rotate the API key for {{ $this->selectedWorkspace->name }}?</h4>
          <div class="modal-sub">A new key replaces the current one right away. Anything still using the old key stops working until the tenant updates it.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep current key</button>
            <button type="button" class="btn btn-danger" wire:click="rotate" wire:loading.attr="disabled" wire:target="rotate">Rotate key</button>
          </div>

        @elseif ($modal === 'delete-webhook' && $this->selectedWebhook)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete this webhook?</h4>
          <div class="modal-sub">{{ $this->selectedWebhook->workspace?->name ?? 'Unknown tenant' }} &middot; <code>{{ $this->endpoint($this->selectedWebhook->url) }}</code><br>The tenant stops receiving these events. They can add a new webhook from their own settings.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep webhook</button>
            <button type="button" class="btn btn-danger" wire:click="deleteWebhook" wire:loading.attr="disabled" wire:target="deleteWebhook">Delete webhook</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
