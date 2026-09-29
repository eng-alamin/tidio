<div class="body">
    <div class="content">
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('overview')" class="{{ $tab === 'overview' ? 'on' : '' }}">Overview</a>
            <a href="#" wire:click.prevent="setTab('sales')" class="{{ $tab === 'sales' ? 'on' : '' }}">Sales</a>
            <a href="#" wire:click.prevent="setTab('leads')" class="{{ $tab === 'leads' ? 'on' : '' }}">Leads</a>
            <a href="#" wire:click.prevent="setTab('ai')" class="{{ $tab === 'ai' ? 'on' : '' }}">AI support</a>
            <a href="#" wire:click.prevent="setTab('human')" class="{{ $tab === 'human' ? 'on' : '' }}">Human support</a>
        </div>

        @if ($tab === 'ai')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setAiSub('live')" class="{{ $aiSub === 'live' ? 'on' : '' }}">Live conversations</a>
                <a href="#" wire:click.prevent="setAiSub('emails')" class="{{ $aiSub === 'emails' ? 'on' : '' }}">Emails</a>
                <a href="#" wire:click.prevent="setAiSub('knowledge')" class="{{ $aiSub === 'knowledge' ? 'on' : '' }}">Knowledge performance</a>
            </div>
        @elseif ($tab === 'human')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setHumanSub('live')" class="{{ $humanSub === 'live' ? 'on' : '' }}">Live conversations</a>
                <a href="#" wire:click.prevent="setHumanSub('tickets')" class="{{ $humanSub === 'tickets' ? 'on' : '' }}">Tickets</a>
                <a href="#" wire:click.prevent="setHumanSub('operators')" class="{{ $humanSub === 'operators' ? 'on' : '' }}">Operators performance</a>
                <a href="#" wire:click.prevent="setHumanSub('hours')" class="{{ $humanSub === 'hours' ? 'on' : '' }}">Online hours</a>
            </div>
        @endif

        <div class="tools">
            <span style="flex:1"></span>
            <input type="date" class="f" wire:model.live="from" style="width:auto">
            <span style="color:var(--soft)">–</span>
            <input type="date" class="f" wire:model.live="to" style="width:auto">
        </div>

        {{-- ===================== OVERVIEW ===================== --}}
        @if ($tab === 'overview')
            @php($d = $this->overview)
            <div class="kpi">
                <div class="card"><small>Live conversations</small><b>{{ $d['live_conversations'] }}</b></div>
                <div class="card"><small>AI resolution rate</small><b>{{ $d['ai_resolution_rate'] }}</b></div>
                <div class="card"><small>Avg. first response</small><b>{{ $d['avg_first_response'] }}</b></div>
                <div class="card"><small>Satisfaction</small><b>{{ $d['satisfaction'] }}</b></div>
            </div>
            <div class="card" style="padding:0;margin-bottom:18px">
                <div style="padding:14px 16px"><b>Conversations per week</b></div>
                <div class="bars">
                    @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                </div>
            </div>

        {{-- ===================== SALES ===================== --}}
        @elseif ($tab === 'sales')
            @php($d = $this->sales)
            <div class="kpi">
                <div class="card"><small>Sales assisted</small><b>{{ $d['sales_assisted'] }}</b></div>
                <div class="card"><small>Orders</small><b>{{ $d['orders'] }}</b></div>
                <div class="card"><small>Conversion rate</small><b>{{ $d['conversion_rate'] }}</b></div>
                <div class="card"><small>Avg. order value</small><b>{{ $d['avg_order_value'] }}</b></div>
            </div>
            <div class="card" style="padding:0;margin-bottom:18px">
                <div style="padding:14px 16px"><b>Assisted revenue per week</b></div>
                <div class="bars">
                    @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                </div>
            </div>
            <div class="card" style="padding:0">
                <table>
                    <tr><th>Product</th><th>Orders</th><th>Revenue</th></tr>
                    @forelse ($d['by_product'] as $row)
                        <tr><td>{{ $row['name'] }}</td><td>{{ $row['orders'] }}</td><td>${{ number_format($row['revenue']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--soft)">No orders in this range.</td></tr>
                    @endforelse
                </table>
            </div>

        {{-- ===================== LEADS ===================== --}}
        @elseif ($tab === 'leads')
            @php($d = $this->leads)
            <div class="kpi">
                <div class="card"><small>Leads acquired</small><b>{{ $d['leads_acquired'] }}</b></div>
                <div class="card"><small>Via chat</small><b>{{ $d['via_chat'] }}</b></div>
                <div class="card"><small>Via flows</small><b>{{ $d['via_flows'] }}</b></div>
                <div class="card"><small>Qualified</small><b>{{ $d['qualified'] }}</b></div>
            </div>
            <div class="card" style="padding:0;margin-bottom:18px">
                <div style="padding:14px 16px"><b>Leads per week</b></div>
                <div class="bars">
                    @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                </div>
            </div>
            <div class="card" style="padding:0">
                <table>
                    <tr><th>Source</th><th>Leads</th><th>Qualified</th></tr>
                    @forelse ($d['by_source'] as $row)
                        <tr><td>{{ $row['source'] }}</td><td>{{ $row['leads'] }}</td><td>{{ $row['qualified'] }}</td></tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--soft)">No leads in this range.</td></tr>
                    @endforelse
                </table>
            </div>

        {{-- ===================== AI SUPPORT ===================== --}}
        @elseif ($tab === 'ai')
            @if ($aiSub === 'live')
                @php($d = $this->aiLive)
                <div class="kpi">
                    <div class="card"><small>AI conversations</small><b>{{ $d['ai_conversations'] }}</b></div>
                    <div class="card"><small>Resolved by AI</small><b>{{ $d['resolved_pct'] }}</b></div>
                    <div class="card"><small>Handed to human</small><b>{{ $d['handed_off_pct'] }}</b></div>
                    <div class="card"><small>Avg. answer time</small><b>{{ $d['avg_answer_time'] }}</b></div>
                </div>
                <div class="card" style="padding:0;margin-bottom:18px">
                    <div style="padding:14px 16px"><b>AI conversations per week</b></div>
                    <div class="bars">
                        @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                    </div>
                </div>
            @elseif ($aiSub === 'emails')
                @php($d = $this->aiEmails)
                <div class="kpi">
                    <div class="card"><small>Emails handled</small><b>{{ $d['emails_handled'] }}</b></div>
                    <div class="card"><small>Resolved by AI</small><b>{{ $d['resolved_pct'] }}</b></div>
                    <div class="card"><small>Drafts sent</small><b>{{ $d['drafts_sent'] }}</b></div>
                    <div class="card"><small>Avg. reply time</small><b>{{ $d['avg_reply_time'] }}</b></div>
                </div>
                <div class="card" style="padding:0;margin-bottom:18px">
                    <div style="padding:14px 16px"><b>AI email replies per week</b></div>
                    <div class="bars">
                        @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                    </div>
                </div>
            @elseif ($aiSub === 'knowledge')
                @php($d = $this->aiKnowledge)
                <div class="kpi">
                    <div class="card"><small>Sources</small><b>{{ $d['sources'] }}</b></div>
                    <div class="card"><small>Answers given</small><b>{{ $d['answers_given'] }}</b></div>
                    <div class="card"><small>Unanswered</small><b>{{ $d['unanswered'] }}</b></div>
                    <div class="card"><small>Top source hit</small><b>{{ $d['top_source_hit'] }}</b></div>
                </div>
                <div class="card" style="padding:0">
                    <table>
                        <tr><th>Source</th><th>Used</th><th>Success rate</th></tr>
                        @forelse ($d['by_source'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['used'] }}</td><td>{{ $row['success_rate'] }}</td></tr>
                        @empty
                            <tr><td colspan="3" style="color:var(--soft)">No knowledge sources yet.</td></tr>
                        @endforelse
                    </table>
                </div>
            @endif

        {{-- ===================== HUMAN SUPPORT ===================== --}}
        @elseif ($tab === 'human')
            @if ($humanSub === 'live')
                @php($d = $this->humanLive)
                <div class="kpi">
                    <div class="card"><small>Conversations</small><b>{{ $d['conversations'] }}</b></div>
                    <div class="card"><small>Avg. first response</small><b>{{ $d['avg_first_response'] }}</b></div>
                    <div class="card"><small>Avg. resolution</small><b>{{ $d['avg_resolution'] }}</b></div>
                    <div class="card"><small>Satisfaction</small><b>{{ $d['satisfaction'] }}</b></div>
                </div>
                <div class="card" style="padding:0;margin-bottom:18px">
                    <div style="padding:14px 16px"><b>Conversations per week</b></div>
                    <div class="bars">
                        @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                    </div>
                </div>
            @elseif ($humanSub === 'tickets')
                @php($d = $this->humanTickets)
                <div class="kpi">
                    <div class="card"><small>New tickets</small><b>{{ $d['new_tickets'] }}</b></div>
                    <div class="card"><small>Solved</small><b>{{ $d['solved'] }}</b></div>
                    <div class="card"><small>Open</small><b>{{ $d['open'] }}</b></div>
                    <div class="card"><small>Avg. resolution</small><b>{{ $d['avg_resolution'] }}</b></div>
                </div>
                <div class="card" style="padding:0;margin-bottom:18px">
                    <div style="padding:14px 16px"><b>Tickets per week</b></div>
                    <div class="bars">
                        @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                    </div>
                </div>
            @elseif ($humanSub === 'operators')
                @php($d = $this->humanOperators)
                <div class="kpi">
                    <div class="card"><small>Active operators</small><b>{{ $d['active_operators'] }}</b></div>
                    <div class="card"><small>Top responder</small><b>{{ $d['top_responder'] }}</b></div>
                    <div class="card"><small>Avg. first response</small><b>{{ $d['avg_first_response'] }}</b></div>
                    <div class="card"><small>Satisfaction</small><b>{{ $d['satisfaction'] }}</b></div>
                </div>
                <div class="card" style="padding:0;margin-bottom:18px">
                    <div style="padding:14px 16px"><b>Conversations handled per week</b></div>
                    <div class="bars">
                        @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                    </div>
                </div>
                <div class="card" style="padding:0">
                    <table>
                        <tr><th>Operator</th><th>Conversations</th><th>Avg. response</th><th>CSAT</th></tr>
                        @forelse ($d['rows'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['conversations'] }}</td><td>{{ $row['avg_response'] }}</td><td>{{ $row['csat'] }}</td></tr>
                        @empty
                            <tr><td colspan="4" style="color:var(--soft)">No operators yet.</td></tr>
                        @endforelse
                    </table>
                </div>
            @elseif ($humanSub === 'hours')
                @php($d = $this->humanHours)
                <div class="kpi">
                    <div class="card"><small>Total online</small><b>{{ $d['total_online'] }}</b></div>
                    <div class="card"><small>Avg. per operator</small><b>{{ $d['avg_per_operator'] }}</b></div>
                    <div class="card"><small>Busiest day</small><b>{{ $d['busiest_day'] }}</b></div>
                    <div class="card"><small>Coverage</small><b>{{ $d['coverage'] }}</b></div>
                </div>
                <div class="card" style="padding:0;margin-bottom:18px">
                    <div style="padding:14px 16px"><b>Online hours per week</b></div>
                    <div class="bars">
                        @foreach ($d['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
