<div>
  <div class="page-head">
    <div><h2>Onboarding</h2><p>How far each tenant got with the setup checklist</p></div>
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Total tenants</div><div class="value">{{ number_format($this->totalTenants) }}</div></div>
    <div class="glass stat-card"><div class="label">Setup complete</div><div class="value" style="color:#4ADE80;">{{ number_format($this->summary['complete']) }}</div></div>
    <div class="glass stat-card"><div class="label">In progress</div><div class="value">{{ number_format($this->summary['in_progress']) }}</div></div>
    <div class="glass stat-card"><div class="label">Not started</div><div class="value">{{ number_format($this->summary['not_started']) }}</div></div>
  </div>

  <div class="glass panel mb-4">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="onbSearch">Search tenants</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search by tenant name…" id="onbSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:170px;" aria-label="Filter by setup status" wire:model.live="statusFilter">
        <option value="">All tenants</option>
        <option value="complete">Setup complete</option>
        <option value="in_progress">In progress</option>
        <option value="not_started">Not started</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Tenant</th><th scope="col">Progress</th><th scope="col">Steps</th><th scope="col">Last activity</th><th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->tenants as $tenant)
            @php
              $row = $this->rowStatus($tenant);
              $percent = (int) round($row['done'] / \App\Support\OnboardingSteps::total() * 100);
              $tenantStatus = $this->tenantStatus($tenant);
            @endphp
            <tr wire:key="onb-{{ $tenant->id }}">
              <td data-label="Tenant" class="cell-main-td">
                <div class="cell-main">
                  <div class="cell-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($tenant->name, 0, 2)) }}</div>
                  <div>
                    <div class="cell-title">{{ $tenant->name }}</div>
                    <span class="status-pill {{ $tenantStatus->cssClass() }}" style="margin-top:4px;">{{ $tenantStatus->label() }}</span>
                  </div>
                </div>
              </td>
              <td data-label="Progress" style="min-width:150px;">
                <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $tenant->name }} setup progress">
                  <div class="progress-bar" style="width:{{ $percent }}%; background:var(--cobalt);"></div>
                </div>
                <div style="color:var(--text-soft); font-size:.74rem; margin-top:4px;">{{ $percent }}%</div>
              </td>
              <td data-label="Steps">
                <div class="d-flex gap-2 flex-wrap">
                  @foreach (\App\Support\OnboardingSteps::STEPS as $key => $meta)
                    @php $done = isset($this->completed[$key][$tenant->id]); @endphp
                    <i class="bi {{ $meta['icon'] }}" title="{{ $meta['label'] }}: {{ $done ? 'done' : 'not done' }}"
                       style="color:{{ $done ? $meta['color'] : 'var(--text-soft)' }}; opacity:{{ $done ? 1 : .35 }};" aria-hidden="true"></i>
                    <span class="sr-only">{{ $meta['label'] }}: {{ $done ? 'done' : 'not done' }}</span>
                  @endforeach
                </div>
                <div style="color:var(--text-soft); font-size:.74rem; margin-top:4px;">{{ $row['done'] }} of {{ \App\Support\OnboardingSteps::total() }}</div>
              </td>
              <td data-label="Last activity">{{ isset($this->lastActivity[$tenant->id]) ? $this->lastActivity[$tenant->id]->diffForHumans() : '—' }}</td>
              <td data-label="Status"><span class="status-pill {{ $row['class'] }}">{{ $row['label'] }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-list-check"></i></div>
                  <h6>No tenants found</h6>
                  <p>Try a different search or filter.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->tenants->links() }}</div>
  </div>

  <div class="glass panel">
    <div class="panel-title"><h5>Checklist steps</h5></div>
    <div class="grid-4">
      @foreach ($this->stepStats as $step)
        <div class="stat-card" style="padding:14px;" wire:key="step-{{ $step['key'] }}">
          <i class="bi {{ $step['icon'] }}" style="color:{{ $step['color'] }};" aria-hidden="true"></i>
          <b style="font-size:.9rem; margin-top:6px;">{{ $step['label'] }}</b>
          <span style="color:var(--text-soft); font-size:.78rem;">{{ $step['percent'] }}% of tenants · {{ number_format($step['count']) }}</span>
        </div>
      @endforeach
    </div>
  </div>
</div>
