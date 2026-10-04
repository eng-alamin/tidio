<div>
  <div class="page-head">
    <div><h2>Coupons</h2><p>Discount codes for tenant invoices</p></div>
    @if ($this->canManage())
      <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>New coupon</button>
    @endif
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Active coupons</div><div class="value">{{ number_format($this->stats['active']) }}</div></div>
    <div class="glass stat-card"><div class="label">Total redemptions</div><div class="value">{{ number_format($this->stats['redemptions']) }}</div></div>
    <div class="glass stat-card"><div class="label">Expiring in 30 days</div><div class="value">{{ number_format($this->stats['expiring']) }}</div></div>
    <div class="glass stat-card"><div class="label">Expired or off</div><div class="value">{{ number_format($this->stats['ended']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="couponSearch">Search coupons</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search by code…" id="couponSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:170px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="scheduled">Scheduled</option>
        <option value="expired">Expired</option>
        <option value="exhausted">Used up</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Code</th><th scope="col">Discount</th><th scope="col">Redemptions</th>
            <th scope="col">Valid</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->coupons as $coupon)
            @php $badge = $this->status($coupon); @endphp
            <tr wire:key="coupon-{{ $coupon->id }}">
              <td data-label="Code" class="cell-main-td"><code style="font-size:.82rem;">{{ $coupon->code }}</code></td>
              <td data-label="Discount">{{ $this->discountLabel($coupon) }}</td>
              <td data-label="Redemptions">
                {{ number_format($coupon->times_redeemed) }}
                <span style="color:var(--text-soft);">/ {{ $coupon->max_redemptions !== null ? number_format($coupon->max_redemptions) : '∞' }}</span>
              </td>
              <td data-label="Valid">{{ $this->validityLabel($coupon) }}</td>
              <td data-label="Status"><span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
              <td data-label="Actions">
                <div class="row-actions">
                  @if ($this->canManage())
                    <button class="icon-action" type="button" wire:click="openEdit({{ $coupon->id }})" aria-label="Edit {{ $coupon->code }}"><i class="bi bi-pencil"></i></button>
                    <button class="icon-action" type="button" wire:click="toggleActive({{ $coupon->id }})" wire:loading.attr="disabled" wire:target="toggleActive({{ $coupon->id }})"
                            aria-label="{{ $coupon->is_active ? 'Deactivate' : 'Activate' }} {{ $coupon->code }}">
                      <i class="bi {{ $coupon->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                    </button>
                  @endif
                  @if ($this->canDelete())
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $coupon->id }})" aria-label="Delete {{ $coupon->code }}"><i class="bi bi-trash3"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-ticket-perforated"></i></div>
                  <h6>No coupons found</h6>
                  <p>{{ $this->canManage() ? 'Try a different search, or create a new coupon.' : 'Try a different search or filter.' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->coupons->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="coupon-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          @php
            $isEdit = $editingId !== null;
            $locked = $this->editingIsUsed;
          @endphp
          <h4>{{ $isEdit ? 'Edit coupon' : 'New coupon' }}</h4>
          <div class="modal-sub">{{ $isEdit ? 'Update the rules for this code.' : 'Create a discount code for tenant invoices.' }}</div>

          @if ($locked)
            <div class="modal-sub" style="color:var(--warn, var(--text-soft));">This coupon has been used, so its code and discount are locked. You can still change the dates, the limit and whether it's active.</div>
          @endif

          <form wire:submit="save">
            <div class="form-field">
              <label class="form-label" for="f-ccode">Code</label>
              <input class="form-control @error('code') is-invalid @enderror" id="f-ccode" wire:model="code" placeholder="e.g. SUMMER25" maxlength="50"
                     style="text-transform:uppercase;" autocomplete="off" @disabled($locked) @unless($locked) autofocus @endunless>
              @error('code')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-ctype">Discount</label>
              <div class="d-flex gap-2">
                <select class="form-select" id="f-ctype" style="max-width:150px;" wire:model.live="discountType" @disabled($locked)>
                  <option value="percent">Percent (%)</option>
                  <option value="fixed">Fixed ($)</option>
                </select>
                <input class="form-control @error('discountValue') is-invalid @enderror" id="f-cvalue" wire:model="discountValue" inputmode="decimal"
                       placeholder="{{ $discountType === 'percent' ? 'e.g. 20' : 'e.g. 10.00' }}" aria-label="Discount amount" @disabled($locked)>
              </div>
              @error('discountValue')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              @error('discountType')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-cfrom">Valid from (optional)</label>
              <input class="form-control @error('validFrom') is-invalid @enderror" id="f-cfrom" type="date" wire:model="validFrom">
              @error('validFrom')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-cuntil">Valid until (optional)</label>
              <input class="form-control @error('validUntil') is-invalid @enderror" id="f-cuntil" type="date" wire:model="validUntil">
              @error('validUntil')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-cmax">Redemption limit (optional)</label>
              <input class="form-control @error('maxRedemptions') is-invalid @enderror" id="f-cmax" wire:model="maxRedemptions" inputmode="numeric" placeholder="Empty means unlimited">
              @error('maxRedemptions')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="f-cactive" wire:model="isActive">
                <label class="form-check-label" for="f-cactive">Coupon is active</label>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">{{ $isEdit ? 'Save changes' : 'Create coupon' }}</button>
            </div>
          </form>

        @elseif ($modal === 'delete' && $this->editing)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-trash3"></i></div>
          <h4>Delete {{ $this->editing->code }}?</h4>
          <div class="modal-sub">Tenants can no longer use this code. Existing invoices keep showing it, and the code stays reserved so it can't be created again.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep coupon</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete coupon</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
