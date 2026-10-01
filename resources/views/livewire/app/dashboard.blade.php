<div class="body">
    <div class="content">
        @php($steps = $this->setupSteps)
        @php($setupDone = count(array_filter($steps)))
        @php($setupTotal = count($steps))

        @unless ($this->setupDismissed || $setupDone === $setupTotal)
            <div class="setup">
                <span class="ring">{{ $setupDone }}/{{ $setupTotal }}</span>
                <div>
                    <h3>Finalize your Loop setup</h3>
                    <p>Just a few minutes to delight your customers.</p>
                </div>
                <button class="btn" wire:click="skipSetup">Skip</button>
                <a class="btn pri" href="{{ route('app.settings.installation') }}">Finish setup</a>
            </div>
        @endunless

        <h2 class="sec">Quick actions</h2>
        @php($q = $this->quickActions)
        <div class="grid">
            <div class="card"><small>Live conversations</small><a href="{{ route('app.inbox') }}">{{ $q['unassignedChats'] }} unassigned</a></div>
            <div class="card"><small>Tickets</small><a href="{{ route('app.inbox') }}">{{ $q['unassignedTickets'] }} unassigned</a></div>
            <div class="card"><small>Lyro AI Agent</small><a href="{{ route('app.lyro') }}">{{ $q['unansweredQuestions'] }} unanswered questions</a></div>
            <div class="card"><small>Flows</small><a href="{{ route('app.flows') }}">{{ $q['activeFlows'] }} active {{ \Illuminate\Support\Str::plural('Flow', $q['activeFlows']) }}</a></div>
            <div class="card"><small>Live visitors</small><a href="{{ route('app.customers') }}">{{ $q['onlineVisitors'] }} on your site</a></div>
        </div>

        <h2 class="sec">Performance</h2>
        @php($p = $this->performance)
        <div class="card" style="padding:0">
            <div class="tabs">
                <div class="{{ $perfTab === 'interactions' ? 'on' : '' }}" wire:click="setPerfTab('interactions')" style="cursor:pointer">Interactions<b>{{ $p['interactions']['value'] }}</b></div>
                <div class="{{ $perfTab === 'ai' ? 'on' : '' }}" wire:click="setPerfTab('ai')" style="cursor:pointer">AI resolution rate<b>{{ $p['ai']['value'] }}</b></div>
                <div class="{{ $perfTab === 'sales' ? 'on' : '' }}" wire:click="setPerfTab('sales')" style="cursor:pointer">Sales assisted<b>{{ $p['sales']['value'] }}</b></div>
                <div class="{{ $perfTab === 'leads' ? 'on' : '' }}" wire:click="setPerfTab('leads')" style="cursor:pointer">Leads acquired<b>{{ $p['leads']['value'] }}</b></div>
            </div>
            <div class="chart bars" style="padding:16px">
                @foreach ($p[$perfTab]['bars'] as $h) <i style="height:{{ $h }}%"></i> @endforeach
            </div>
        </div>
    </div>

    <aside class="side">
        <h3>Project status</h3>
        @php($ps = $this->projectStatus)
        <div class="card">
            <p>
                <b>Chat widget</b>
                @if ($ps['widget_installed']) <i class="bi bi-check-circle" style="color:var(--ok)"></i><br>Installed
                @else <i class="bi bi-exclamation-circle bad"></i><br>Not installed<br><a href="{{ route('app.settings.installation') }}">Install chat widget</a>
                @endif
            </p>
            <p>
                <b>Mailbox</b>
                @if ($ps['mailbox_connected']) <br>Connected
                @else <br><a href="{{ route('app.settings.email') }}">Connect your mailbox</a>
                @endif
            </p>
            <p>
                <b>Domains</b>
                @if ($ps['domain_connected']) <br>Connected
                @else <br><a href="{{ route('app.settings.email') }}">Connect domain</a>
                @endif
            </p>
        </div>

        <h3>Current usage</h3>
        @php($u = $this->usage)
        <div class="card">
            <p><b>Customer service</b><br>Billable conversations {{ $u['conversations']['used'] }} / {{ $u['conversations']['limit'] ?? '∞' }}</p>
            <div class="bar"><i style="width:{{ $u['conversations']['pct'] }}%"></i></div>
            <p><b>Lyro AI Agent</b><br>AI conversations {{ $u['ai_conversations']['used'] }} / {{ $u['ai_conversations']['limit'] ?? '∞' }}</p>
            <div class="bar"><i style="width:{{ $u['ai_conversations']['pct'] }}%"></i></div>
        </div>
    </aside>
</div>
