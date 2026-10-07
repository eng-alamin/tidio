<div>
  @php
    $signups = $this->signups;
    $tickets = $this->tickets;
    $kpis = $this->kpis;
  @endphp
  <div class="page-head"><div><h2>Analytics</h2><p>Platform health across all tenants</p></div></div>

  <div class="grid-2 mb-4">
    <div class="glass panel">
      <div class="panel-title"><h5>New signups (12 weeks)</h5><span style="color:var(--text-soft); font-size:.8rem;">{{ number_format(array_sum(array_column($signups, 'count'))) }} total</span></div>
      <div class="mini-bar" style="height:140px;" role="img"
           aria-label="New workspaces per week for the last 12 weeks: {{ implode(', ', array_column($signups, 'count')) }}">
        @foreach ($signups as $week)
          <span title="Week of {{ $week['label'] }}: {{ $week['count'] }}" style="height:{{ $week['height'] }}%; background:linear-gradient(180deg,var(--citrus),rgba(255,201,60,.15));"></span>
        @endforeach
      </div>
      <div class="d-flex justify-content-between mt-2" style="color:var(--text-soft); font-size:.74rem;">
        <span>{{ $signups[0]['label'] }}</span><span>{{ $signups[array_key_last($signups)]['label'] }}</span>
      </div>
    </div>

    <div class="glass panel">
      <div class="panel-title"><h5>Support ticket volume (12 weeks)</h5><span style="color:var(--text-soft); font-size:.8rem;">{{ number_format(array_sum(array_column($tickets, 'count'))) }} total</span></div>
      <div class="mini-bar" style="height:140px;" role="img"
           aria-label="Tickets opened per week for the last 12 weeks: {{ implode(', ', array_column($tickets, 'count')) }}">
        @foreach ($tickets as $week)
          <span title="Week of {{ $week['label'] }}: {{ $week['count'] }}" style="height:{{ $week['height'] }}%; background:linear-gradient(180deg,var(--violet),rgba(139,92,246,.15));"></span>
        @endforeach
      </div>
      <div class="d-flex justify-content-between mt-2" style="color:var(--text-soft); font-size:.74rem;">
        <span>{{ $tickets[0]['label'] }}</span><span>{{ $tickets[array_key_last($tickets)]['label'] }}</span>
      </div>
    </div>
  </div>

  <div class="grid-4">
    <div class="glass stat-card">
      <div class="label">Avg. first response</div>
      <div class="value">{{ $kpis['first_response'] ?? '—' }}</div>
      <div class="delta"><span style="color:var(--text-soft);">Last {{ $kpis['days'] }} days</span></div>
    </div>
    <div class="glass stat-card">
      <div class="label">CSAT score</div>
      <div class="value">{{ $kpis['csat'] !== null ? $kpis['csat'].'/5' : '—' }}</div>
      <div class="delta"><span style="color:var(--text-soft);">{{ number_format($kpis['csat_count']) }} ratings, last {{ $kpis['days'] }} days</span></div>
    </div>
    <div class="glass stat-card">
      <div class="label">Messages / day</div>
      <div class="value">{{ number_format($kpis['messages_per_day']) }}</div>
      <div class="delta"><span style="color:var(--text-soft);">Average, private notes excluded</span></div>
    </div>
    <div class="glass stat-card">
      <div class="label">Conversations solved</div>
      <div class="value">{{ $kpis['resolution_rate'] !== null ? $kpis['resolution_rate'].'%' : '—' }}</div>
      <div class="delta"><span style="color:var(--text-soft);">Of {{ number_format($kpis['conversations']) }} in the last {{ $kpis['days'] }} days</span></div>
    </div>
  </div>
</div>
