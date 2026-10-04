<div>
  <div class="page-head"><div><h2>Subscriptions</h2><p>Plan changes, trials and renewals</p></div></div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Active Subscriptions</div><div class="value">{{ number_format($this->stats['active']) }}</div></div>
    <div class="glass stat-card"><div class="label">Trials in progress</div><div class="value">{{ number_format($this->stats['trialing']) }}</div></div>
    <div class="glass stat-card"><div class="label">Past due</div><div class="value" style="color:var(--danger)">{{ number_format($this->stats['past_due']) }}</div></div>
    <div class="glass stat-card"><div class="label">Cancelled (30d)</div><div class="value">{{ number_format($this->stats['cancelled']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="subSearch">Search by tenant</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search tenants…" id="subSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:160px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="trialing">Trial</option>
        <option value="past_due">Past due</option>
        <option value="cancelled">Cancelled</option>
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
            <th scope="col">Tenant</th><th scope="col">Plan</th><th scope="col">Billing cycle</th><th scope="col">Seats</th>
            <th scope="col">Next renewal</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->subscriptions as $subscription)
            @php
              $cancelled = $subscription->status === \App\Enums\SubscriptionStatus::Cancelled;
              $statusClass = match ($subscription->status) {
                  \App\Enums\SubscriptionStatus::Active => 'status-active',
                  \App\Enums\SubscriptionStatus::Trialing => 'status-trial',
                  \App\Enums\SubscriptionStatus::PastDue => 'status-past',
                  default => 'status-suspended',
              };
              $statusLabel = match ($subscription->status) {
                  \App\Enums\SubscriptionStatus::Active => 'Active',
                  \App\Enums\SubscriptionStatus::Trialing => 'Trial',
                  \App\Enums\SubscriptionStatus::PastDue => 'Past due',
                  default => 'Cancelled',
              };
            @endphp
            <tr wire:key="sub-{{ $subscription->id }}">
              <td data-label="Tenant" class="cell-main-td">
                <div class="cell-main">
                  <div class="cell-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($subscription->workspace->name, 0, 2)) }}</div>
                  <div class="cell-title">{{ $subscription->workspace->name }}</div>
                </div>
              </td>
              <td data-label="Plan"><span class="plan-tag">{{ $subscription->plan?->name ?? $subscription->plan_name }}</span></td>
              <td data-label="Cycle">{{ $subscription->billing_cycle?->label() ?? 'Monthly' }}</td>
              <td data-label="Seats">{{ $subscription->seats }}</td>
              <td data-label="Renewal">{{ $cancelled || ! $subscription->renews_at ? '—' : $subscription->renews_at->format('Y-m-d') }}</td>
              <td data-label="Status"><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td data-label="Actions">
                <div class="row-actions">
                  @if ($this->canManage())
                    <button class="icon-action" type="button" wire:click="openEdit({{ $subscription->id }})" aria-label="Edit {{ $subscription->workspace->name }} subscription"><i class="bi bi-pencil"></i></button>
                    @unless ($cancelled)
                      <button class="icon-action danger" type="button" wire:click="confirmCancel({{ $subscription->id }})" aria-label="Cancel {{ $subscription->workspace->name }} subscription"><i class="bi bi-x-octagon"></i></button>
                    @endunless
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-credit-card"></i></div>
                  <h6>No subscriptions found</h6>
                  <p>Subscriptions show up here once a tenant starts a trial or subscribes.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->subscriptions->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="sub-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          <h4>Manage subscription</h4>
          <div class="modal-sub">{{ $this->editingSubscription?->workspace?->name }}</div>

          <form wire:submit="save" novalidate>
            <div class="form-field">
              <label class="form-label" for="f-splan">Plan</label>
              <select class="form-select @error('planId') is-invalid @enderror" id="f-splan" wire:model="planId">
                @foreach ($this->plans as $plan)
                  <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                @endforeach
              </select>
              @error('planId')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-scycle">Billing cycle</label>
              <select class="form-select @error('billingCycle') is-invalid @enderror" id="f-scycle" wire:model="billingCycle">
                @foreach (\App\Enums\BillingCycle::cases() as $cycle)
                  <option value="{{ $cycle->value }}">{{ $cycle->label() }}</option>
                @endforeach
              </select>
              @error('billingCycle')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-sseats">Seats</label>
              <input class="form-control @error('seats') is-invalid @enderror" id="f-sseats" type="number" min="1" max="10000" wire:model="seats">
              @error('seats')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-sstatus">Status</label>
              <select class="form-select @error('status') is-invalid @enderror" id="f-sstatus" wire:model.live="status">
                <option value="trialing">Trial</option>
                <option value="active">Active</option>
                <option value="past_due">Past due</option>
                <option value="cancelled">Cancelled</option>
              </select>
              @error('status')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-srenew">{{ $status === 'trialing' ? 'Trial ends' : 'Next renewal' }}</label>
              <input class="form-control @error('renewsAt') is-invalid @enderror" id="f-srenew" type="date" wire:model="renewsAt">
              @error('renewsAt')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">Save changes</button>
            </div>
          </form>

        @elseif ($modal === 'cancel')
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Cancel {{ $this->editingSubscription?->workspace?->name }}'s subscription?</h4>
          <div class="modal-sub">The subscription is marked cancelled right away and stops counting towards MRR. You can bring it back later by editing its status.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep subscription</button>
            <button type="button" class="btn btn-danger" wire:click="cancel" wire:loading.attr="disabled" wire:target="cancel">Cancel subscription</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
