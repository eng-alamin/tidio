<div>
  <div class="page-head">
    <div><h2>Tenants</h2><p>Every workspace running on Loop</p></div>
    @if ($this->canManage())
      <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>New Tenant</button>
    @endif
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="tenantSearch">Search tenants</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search tenants…" id="tenantSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:160px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="trial">Trial</option>
        <option value="past_due">Past due</option>
        <option value="cancelled">Cancelled</option>
        <option value="suspended">Suspended</option>
      </select>

      <select class="form-select" style="max-width:160px;" aria-label="Filter by plan" wire:model.live="planFilter">
        <option value="">All plans</option>
        @foreach ($this->plans as $plan)
          <option value="{{ $plan->slug }}">{{ $plan->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,planFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Tenant</th><th scope="col">Owner</th><th scope="col">Plan</th><th scope="col">Status</th>
            <th scope="col">Agents</th><th scope="col">MRR</th><th scope="col">Joined</th><th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->tenants as $tenant)
            @php
              $status = \App\Enums\TenantStatus::fromWorkspace($tenant);
              $subscription = $tenant->activeSubscription;
              $mrr = $subscription && $status === \App\Enums\TenantStatus::Active ? $subscription->monthlyAmountCents() / 100 : 0;
            @endphp
            <tr wire:key="tenant-{{ $tenant->id }}">
              <td data-label="Tenant" class="cell-main-td">
                <div class="cell-main">
                  <div class="cell-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($tenant->name, 0, 2)) }}</div>
                  <div>
                    <div class="cell-title">{{ $tenant->name }}</div>
                    <div class="cell-sub">{{ $tenant->owner?->email }}</div>
                  </div>
                </div>
              </td>
              <td data-label="Owner">{{ $tenant->owner?->name ?? '—' }}</td>
              <td data-label="Plan"><span class="plan-tag">{{ $subscription?->plan?->name ?? ucfirst($tenant->plan) }}</span></td>
              <td data-label="Status">
                @if ($this->canManage())
                  @if ($tenant->is_suspended)
                    <button class="status-toggle" type="button" wire:click="unsuspend({{ $tenant->id }})" aria-pressed="false" aria-label="Reactivate {{ $tenant->name }}" @if($tenant->suspension_reason) title="{{ $tenant->suspension_reason }}" @endif>
                      <span class="switch"></span><span class="status-pill {{ $status->cssClass() }}">{{ $status->label() }}</span>
                    </button>
                  @else
                    <button class="status-toggle" type="button" wire:click="confirmSuspend({{ $tenant->id }})" aria-pressed="true" aria-label="Suspend {{ $tenant->name }}, currently {{ $status->label() }}">
                      <span class="switch on"></span><span class="status-pill {{ $status->cssClass() }}">{{ $status->label() }}</span>
                    </button>
                  @endif
                @else
                  <span class="status-pill {{ $status->cssClass() }}">{{ $status->label() }}</span>
                @endif
              </td>
              <td data-label="Agents">{{ $tenant->agents_count }}</td>
              <td data-label="MRR">${{ number_format($mrr, 0) }}</td>
              <td data-label="Joined">{{ $tenant->created_at->format('Y-m-d') }}</td>
              <td data-label="Actions">
                <div class="row-actions">
                  @if ($this->canManage() || $this->canChangePlan())
                    <button class="icon-action" type="button" wire:click="openEdit({{ $tenant->id }})" aria-label="Edit {{ $tenant->name }}"><i class="bi bi-pencil"></i></button>
                  @endif
                  @if ($this->canManage())
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $tenant->id }})" aria-label="Delete {{ $tenant->name }}"><i class="bi bi-trash3"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-buildings"></i></div>
                  <h6>No tenants found</h6>
                  <p>Try a different search or filter{{ $this->canManage() ? ', or create a new tenant' : '' }}.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->tenants->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="tenant-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          @php $isEdit = $editingId !== null; @endphp
          <h4>{{ $isEdit ? 'Edit tenant' : 'Create new tenant' }}</h4>
          <div class="modal-sub">{{ $isEdit ? 'Update this workspace.' : 'Spin up a new workspace on Loop (14-day trial).' }}</div>

          <form wire:submit="save" novalidate>
            <div class="form-field">
              <label class="form-label" for="f-tname">Company name</label>
              <input class="form-control @error('name') is-invalid @enderror" id="f-tname" wire:model="name" placeholder="e.g. Acme Corp" @disabled($isEdit && ! $this->canManage()) autofocus>
              @error('name')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            @if ($isEdit)
              <div class="form-field">
                <label class="form-label">Owner</label>
                <div style="font-size:.88rem;">{{ $ownerName }} <span style="color:var(--text-soft);">· {{ $ownerEmail }}</span></div>
              </div>
            @else
              <div class="form-field">
                <label class="form-label" for="f-towner">Owner name</label>
                <input class="form-control @error('ownerName') is-invalid @enderror" id="f-towner" wire:model="ownerName" placeholder="e.g. Jordan Lee">
                @error('ownerName')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>
              <div class="form-field">
                <label class="form-label" for="f-temail">Owner email</label>
                <input class="form-control @error('ownerEmail') is-invalid @enderror" id="f-temail" type="email" wire:model="ownerEmail" placeholder="owner@company.com" autocomplete="off">
                @error('ownerEmail')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>
              <div class="form-field">
                <label class="form-label" for="f-tpass">Temporary password</label>
                <input class="form-control @error('ownerPassword') is-invalid @enderror" id="f-tpass" type="password" wire:model="ownerPassword" placeholder="At least 8 characters" autocomplete="new-password">
                @error('ownerPassword')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>
            @endif

            <div class="form-field">
              <label class="form-label" for="f-tplan">Plan</label>
              <select class="form-select @error('planId') is-invalid @enderror" id="f-tplan" wire:model="planId">
                @foreach ($this->plans as $plan)
                  <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                @endforeach
              </select>
              @error('planId')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">{{ $isEdit ? 'Save changes' : 'Create tenant' }}</button>
            </div>
          </form>

        @elseif ($modal === 'suspend')
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-pause-circle"></i></div>
          <h4>Suspend {{ $this->editingTenant?->name }}?</h4>
          <div class="modal-sub">Every member will be locked out of this workspace until you reactivate it.</div>
          <form wire:submit="suspend" novalidate>
            <div class="form-field">
              <label class="form-label" for="f-treason">Reason (optional)</label>
              <textarea class="form-control @error('suspendReason') is-invalid @enderror" id="f-treason" rows="3" wire:model="suspendReason" placeholder="e.g. Repeated payment failures"></textarea>
              @error('suspendReason')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>
            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-danger" wire:loading.attr="disabled" wire:target="suspend">Suspend tenant</button>
            </div>
          </form>

        @elseif ($modal === 'delete')
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete {{ $this->editingTenant?->name }}?</h4>
          <div class="modal-sub">The workspace is removed from the platform and its subscriptions are cancelled. Data is kept (soft delete) so it can be restored by a developer.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete tenant</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
