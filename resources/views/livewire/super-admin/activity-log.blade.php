<div>
  <div class="page-head"><div><h2>Activity &amp; Audit Log</h2><p>Every action platform staff take in this panel</p></div></div>

  <div class="glass panel mb-3">
    <div class="d-flex gap-2 flex-wrap">
      <label class="sr-only" for="logSearch">Search the log</label>
      <input class="form-control" style="max-width:230px;" id="logSearch" placeholder="Search action or tenant…" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:200px;" aria-label="Filter by person" wire:model.live="actorFilter">
        <option value="">Everyone</option>
        @foreach ($this->admins as $admin)
          <option value="{{ $admin->id }}">{{ $admin->name }}</option>
        @endforeach
        <option value="system">System</option>
      </select>

      <select class="form-select" style="max-width:200px;" aria-label="Filter by area" wire:model.live="typeFilter">
        <option value="">All areas</option>
        @foreach ($this->types as $name => $label)
          <option value="{{ $name }}">{{ $label }}</option>
        @endforeach
      </select>

      <input class="form-control" type="date" style="max-width:170px;" aria-label="From date" wire:model.live="dateFrom">
      <input class="form-control" type="date" style="max-width:170px;" aria-label="To date" wire:model.live="dateTo">
      <button class="btn btn-ghost" type="button" wire:click="resetFilters">Reset</button>
    </div>
  </div>

  <div class="glass panel">
    <div wire:loading.class="opacity-50" wire:target="search,actorFilter,typeFilter,dateFrom,dateTo,resetFilters,gotoPage,previousPage,nextPage">
      @forelse ($this->entries as $entry)
        @php($subject = $this->subjectLabel($entry))
        <div class="d-flex gap-3 align-items-start" style="padding:12px 0; border-bottom:1px solid var(--border-soft);" wire:key="log-{{ $entry->id }}">
          <div class="stat-icon" style="width:34px;height:34px;font-size:.85rem;background:var(--cobalt-soft); color:#7EA1F5; margin-bottom:0;" aria-hidden="true"><i class="bi {{ $this->typeIcon($entry->log_name) }}"></i></div>
          <div style="flex:1; min-width:0;">
            <div style="font-size:.87rem;">
              {{ $entry->description }}@if ($subject): <strong>{{ $subject }}</strong>@endif
            </div>
            <div class="cell-sub">
              {{ $this->actorName($entry) }} · {{ $this->typeLabel($entry->log_name) }} ·
              <time datetime="{{ $entry->created_at->toIso8601String() }}" title="{{ $entry->created_at->format('Y-m-d H:i:s') }}">{{ $entry->created_at->diffForHumans() }}</time>
            </div>
          </div>
          <button class="icon-action" type="button" wire:click="openDetail({{ $entry->id }})" aria-label="View details: {{ $entry->description }}"><i class="bi bi-eye"></i></button>
        </div>
      @empty
        <div class="state-block">
          <div class="state-icon" aria-hidden="true"><i class="bi bi-terminal"></i></div>
          <h6>No matching activity</h6>
          <p>Try widening your filters.</p>
          <button class="btn btn-ghost" type="button" wire:click="resetFilters">Reset filters</button>
        </div>
      @endforelse
    </div>

    <div class="mt-3">{{ $this->entries->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ details --}}
  @if ($detailId && $this->selected)
    @php($entry = $this->selected)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="log-modal" wire:keydown.escape.window="closeDetail">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeDetail" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>
        <h4>{{ $entry->description }}</h4>
        <div class="modal-sub">{{ $this->typeLabel($entry->log_name) }}</div>

        <div class="detail-row"><span class="k">Done by</span><span>{{ $this->actorName($entry) }}</span></div>
        <div class="detail-row"><span class="k">When</span><span>{{ $entry->created_at->format('Y-m-d H:i:s') }}</span></div>
        @foreach ($this->detailRows($entry) as $label => $value)
          <div class="detail-row"><span class="k">{{ $label }}</span><span style="text-align:right; word-break:break-word;">{{ $value }}</span></div>
        @endforeach

        <div class="modal-actions">
          <button type="button" class="btn btn-ghost" wire:click="closeDetail">Close</button>
        </div>
      </div>
    </div>
  @endif
</div>
