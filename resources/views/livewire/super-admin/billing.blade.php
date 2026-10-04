<div>
  <div class="page-head">
    <div><h2>Billing &amp; Invoices</h2><p>Platform-wide payment history</p></div>
    @if ($this->canManage())
      <button class="btn btn-ghost" type="button" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"><i class="bi bi-download" aria-hidden="true"></i>Export CSV</button>
    @endif
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Collected (30d, USD)</div><div class="value">${{ number_format($this->stats['collected'], 0) }}</div></div>
    <div class="glass stat-card"><div class="label">Outstanding (USD)</div><div class="value">${{ number_format($this->stats['outstanding'], 0) }}</div></div>
    <div class="glass stat-card"><div class="label">Overdue invoices</div><div class="value" style="color:var(--danger)">{{ number_format($this->stats['overdue']) }}</div></div>
    <div class="glass stat-card"><div class="label">Created this month</div><div class="value">{{ number_format($this->stats['this_month']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="invSearch">Search invoices</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search invoice or tenant…" id="invSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:170px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="paid">Paid</option>
        <option value="open">Open</option>
        <option value="overdue">Overdue</option>
        <option value="draft">Draft</option>
        <option value="void">Void</option>
        <option value="uncollectible">Uncollectible</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Invoice</th><th scope="col">Tenant</th><th scope="col">Amount</th>
            <th scope="col">Date</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->invoices as $invoice)
            @php $badge = $this->badge($invoice); @endphp
            <tr wire:key="invoice-{{ $invoice->id }}">
              <td data-label="Invoice" class="cell-main-td">{{ $invoice->invoice_number }}</td>
              <td data-label="Tenant">
                {{ $invoice->workspace?->name ?? '—' }}
                @if ($invoice->workspace?->trashed()) <span style="color:var(--text-soft);">(deleted)</span> @endif
              </td>
              <td data-label="Amount">{{ $this->money($invoice) }}</td>
              <td data-label="Date">{{ ($invoice->issued_at ?? $invoice->created_at)->format('Y-m-d') }}</td>
              <td data-label="Status"><span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
              <td data-label="Actions">
                <div class="row-actions">
                  <button class="icon-action" type="button" wire:click="openView({{ $invoice->id }})" aria-label="View invoice {{ $invoice->invoice_number }}"><i class="bi bi-eye"></i></button>
                  @if ($this->canMarkPaid($invoice))
                    <button class="icon-action" type="button" wire:click="confirmPay({{ $invoice->id }})" aria-label="Mark invoice {{ $invoice->invoice_number }} as paid"><i class="bi bi-check2-circle"></i></button>
                  @endif
                  @if ($this->canVoid($invoice))
                    <button class="icon-action danger" type="button" wire:click="confirmVoid({{ $invoice->id }})" aria-label="Void invoice {{ $invoice->invoice_number }}"><i class="bi bi-slash-circle"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-receipt"></i></div>
                  <h6>No invoices found</h6>
                  <p>Invoices appear here as tenants are billed. Try a different search or filter.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->invoices->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal && $this->selected)
    @php $inv = $this->selected; @endphp
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="invoice-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'view')
          @php
            $badge = $this->badge($inv);
            $pdf = $this->safePdfUrl($inv);
          @endphp
          <h4>{{ $inv->invoice_number }}</h4>
          <div class="modal-sub">Invoice</div>
          <div class="detail-row"><span class="k">Tenant</span><span>{{ $inv->workspace?->name ?? '—' }}</span></div>
          <div class="detail-row"><span class="k">Plan</span><span>{{ $inv->subscription?->plan_name ?? '—' }}</span></div>
          <div class="detail-row"><span class="k">Amount</span><span>{{ $this->money($inv) }}</span></div>
          <div class="detail-row"><span class="k">Coupon</span><span>{{ $inv->coupon?->code ?? '—' }}</span></div>
          <div class="detail-row"><span class="k">Issued</span><span>{{ $inv->issued_at?->format('Y-m-d') ?? '—' }}</span></div>
          <div class="detail-row"><span class="k">Due</span><span>{{ $inv->due_at?->format('Y-m-d') ?? '—' }}</span></div>
          <div class="detail-row"><span class="k">Paid</span><span>{{ $inv->paid_at?->format('Y-m-d') ?? '—' }}</span></div>
          <div class="detail-row"><span class="k">Status</span><span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span></div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Close</button>
            @if ($pdf)
              <a class="btn btn-cobalt" href="{{ $pdf }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>Open PDF</a>
            @endif
          </div>

        @elseif ($modal === 'pay')
          <div class="confirm-icon" aria-hidden="true" style="background:var(--ok-soft); color:var(--ok);"><i class="bi bi-check2-circle"></i></div>
          <h4>Mark {{ $inv->invoice_number }} as paid?</h4>
          <div class="modal-sub">{{ $this->money($inv) }} from {{ $inv->workspace?->name ?? 'this tenant' }} will be recorded as paid today. This does not charge a card.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
            <button type="button" class="btn btn-cobalt" wire:click="markPaid" wire:loading.attr="disabled" wire:target="markPaid">Mark as paid</button>
          </div>

        @elseif ($modal === 'void')
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-slash-circle"></i></div>
          <h4>Void {{ $inv->invoice_number }}?</h4>
          <div class="modal-sub">The invoice is cancelled and can no longer be collected. This can't be undone.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep invoice</button>
            <button type="button" class="btn btn-danger" wire:click="voidInvoice" wire:loading.attr="disabled" wire:target="voidInvoice">Void invoice</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
