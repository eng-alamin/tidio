<div>
  <div class="page-head">
    <div><h2>Support Inbox</h2><p>Contact-sales requests from the public website</p></div>
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">New</div><div class="value">{{ number_format($this->stats['new']) }}</div></div>
    <div class="glass stat-card"><div class="label">Contacted</div><div class="value">{{ number_format($this->stats['contacted']) }}</div></div>
    <div class="glass stat-card"><div class="label">Qualified</div><div class="value">{{ number_format($this->stats['qualified']) }}</div></div>
    <div class="glass stat-card"><div class="label">Closed</div><div class="value">{{ number_format($this->stats['closed']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="leadSearch">Search requests</label>
      <input class="form-control" style="max-width:280px;" placeholder="Search by name, email or company…" id="leadSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:170px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="new">New</option>
        <option value="contacted">Contacted</option>
        <option value="qualified">Qualified</option>
        <option value="closed">Closed</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">From</th><th scope="col">Company</th><th scope="col">Source</th><th scope="col">Received</th><th scope="col">Status</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->leads as $lead)
            @php $badge = $this->badge($lead->status); @endphp
            <tr wire:key="lead-{{ $lead->id }}">
              <td data-label="From" class="cell-main-td">{{ $lead->name }}<div style="color:var(--text-soft); font-size:.76rem;">{{ $lead->email }}</div></td>
              <td data-label="Company">{{ $lead->company ?: '—' }}</td>
              <td data-label="Source">{{ $lead->source_page ?: '—' }}</td>
              <td data-label="Received">{{ $lead->created_at?->format('Y-m-d H:i') }}</td>
              <td data-label="Status"><span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
              <td data-label="Actions">
                <div class="row-actions">
                  <button class="icon-action" type="button" wire:click="open({{ $lead->id }})" aria-label="Open request from {{ $lead->name }}"><i class="bi bi-eye"></i></button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-headset"></i></div>
                  <h6>No requests found</h6>
                  <p>Requests appear here when someone fills in the contact-sales form. Try a different search or filter.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->leads->links() }}</div>
  </div>

  @if ($this->viewing)
    @php $lead = $this->viewing; $badge = $this->badge($lead->status); @endphp
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="lead-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>
        <h4>{{ $lead->name }}</h4>
        <div class="modal-sub"><span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span></div>
        <div class="detail-row"><span class="k">Email</span><span>{{ $lead->email }}</span></div>
        <div class="detail-row"><span class="k">Company</span><span>{{ $lead->company ?: '—' }}</span></div>
        <div class="detail-row"><span class="k">Phone</span><span>{{ $lead->phone ?: '—' }}</span></div>
        <div class="detail-row"><span class="k">Source</span><span>{{ $lead->source_page ?: '—' }}</span></div>
        <div class="detail-row"><span class="k">Received</span><span>{{ $lead->created_at?->format('Y-m-d H:i') }}</span></div>
        <div style="margin-top:14px;">
          <div class="form-label">Message</div>
          <div style="white-space:normal; word-break:break-word;">{!! $lead->message ? nl2br(e($lead->message)) : '<span style="color:var(--text-soft);">No message.</span>' !!}</div>
        </div>

        @if ($this->canManage())
          <div class="form-field" style="margin-top:18px;">
            <div class="form-label">Change status</div>
            <div class="d-flex gap-2 flex-wrap">
              @foreach (\App\Enums\LeadStatus::cases() as $case)
                <button type="button" class="btn btn-sm {{ $lead->status === $case ? 'btn-cobalt' : 'btn-ghost' }}"
                        wire:click="setStatus({{ $lead->id }}, '{{ $case->value }}')" wire:loading.attr="disabled" wire:target="setStatus"
                        aria-pressed="{{ $lead->status === $case ? 'true' : 'false' }}">{{ $this->badge($case)['label'] }}</button>
              @endforeach
            </div>
          </div>
        @endif

        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" wire:click="closeModal">Close</button>
        </div>
      </div>
    </div>
  @endif
</div>
