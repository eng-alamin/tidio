<div>
  <div class="page-head">
    <div><h2>Platform Overview</h2><p>Live snapshot across all tenants</p></div>
    <div class="d-flex gap-2">
      <a class="btn btn-cobalt" href="{{ route('admin.tenants', ['create' => 1]) }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>New Tenant</a>
    </div>
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card">
      <div class="stat-icon" style="background:var(--cobalt-soft); color:#7EA1F5;" aria-hidden="true"><i class="bi bi-buildings-fill"></i></div>
      <div class="label">Total Tenants</div>
      <div class="value">{{ number_format($this->totalTenants) }}</div>
      <div class="delta up"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> {{ number_format($this->newTenantsThisMonth) }} new this month</div>
    </div>
    <div class="glass stat-card">
      <div class="stat-icon" style="background:var(--ok-soft); color:#4ADE80;" aria-hidden="true"><i class="bi bi-currency-dollar"></i></div>
      <div class="label">MRR</div>
      <div class="value">${{ number_format($this->mrr, 0) }}</div>
      <div class="delta"><span style="color:var(--text-soft);">Active subscriptions only</span></div>
    </div>
    <div class="glass stat-card">
      <div class="stat-icon" style="background:rgba(255,201,60,.15); color:var(--citrus);" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
      <div class="label">Active Users</div>
      <div class="value">{{ number_format($this->activeUsers) }}</div>
      <div class="delta"><span style="color:var(--text-soft);">Enabled workspace members</span></div>
    </div>
    <div class="glass stat-card">
      <div class="stat-icon" style="background:var(--danger-soft); color:var(--danger);" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div class="label">Churn Rate</div>
      <div class="value">{{ $this->churnRate }}%</div>
      <div class="delta"><span style="color:var(--text-soft);">Last 30 days</span></div>
    </div>
  </div>

  <div class="grid-2 mb-4">
    <div class="glass panel">
      <div class="panel-title"><h5>Revenue Trend</h5></div>
      <div class="state-block">
        <div class="state-icon" aria-hidden="true"><i class="bi bi-graph-up"></i></div>
        <h6>Not enough history yet</h6>
        <p>The platform does not store historical MRR snapshots yet. A scheduled job that records MRR daily will power this chart.</p>
      </div>
    </div>

    <div class="glass panel">
      <div class="panel-title"><h5>Plan Distribution</h5></div>
      @if ($this->planDistribution->isEmpty())
        <div class="state-block"><h6>No plans configured</h6></div>
      @else
        <div class="d-flex flex-column gap-3">
          @foreach ($this->planDistribution as $plan)
            <div>
              <div class="d-flex justify-content-between mb-1">
                <span>{{ $plan['name'] }}</span>
                <span style="color:var(--text-soft)">{{ $plan['percent'] }}% · {{ $plan['count'] }}</span>
              </div>
              <div class="progress" role="progressbar" aria-valuenow="{{ $plan['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $plan['name'] }} plan share">
                <div class="progress-bar" style="width:{{ $plan['percent'] }}%; background:{{ $plan['color'] }};"></div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="glass panel">
    <div class="panel-title">
      <h5>Recently Added Tenants</h5>
      <a href="{{ route('admin.tenants') }}" class="nav-item" style="padding:4px 10px;">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="table-wrap">
      <table class="responsive-table">
        <thead><tr><th scope="col">Tenant</th><th scope="col">Plan</th><th scope="col">Status</th><th scope="col">Users</th><th scope="col">Joined</th></tr></thead>
        <tbody>
          @forelse ($this->recentTenants as $tenant)
            @php($status = \App\Enums\TenantStatus::fromWorkspace($tenant))
            <tr wire:key="recent-{{ $tenant->id }}">
              <td data-label="Tenant" class="cell-main-td">
                <div class="cell-main">
                  <div class="cell-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($tenant->name, 0, 2)) }}</div>
                  <div class="cell-title">{{ $tenant->name }}</div>
                </div>
              </td>
              <td data-label="Plan"><span class="plan-tag">{{ $tenant->activeSubscription?->plan?->name ?? ucfirst($tenant->plan) }}</span></td>
              <td data-label="Status"><span class="status-pill {{ $status->cssClass() }}">{{ $status->label() }}</span></td>
              <td data-label="Users">{{ $tenant->agents_count }}</td>
              <td data-label="Joined">{{ $tenant->created_at->format('Y-m-d') }}</td>
            </tr>
          @empty
            <tr><td colspan="5" style="padding:0;">
              <div class="state-block"><h6>No tenants yet</h6><p>New workspaces will show up here.</p></div>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
