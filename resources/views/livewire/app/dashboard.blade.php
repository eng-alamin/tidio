<div class="body">
    <div class="content">
        <div class="setup">
            <span class="ring">{{ $setupDone }}/{{ $setupTotal }}</span>
            <div>
                <h3>Finalize your Loop setup</h3>
                <p>Just a few minutes to delight your customers.</p>
            </div>
            <button class="btn" wire:click="skipSetup">Skip</button>
            <a class="btn pri" href="#" data-toast="Getting started — coming soon.">Finish setup</a>
        </div>

        <h2 class="sec">Quick actions</h2>
        <div class="grid">
            <div class="card"><small>Live conversations</small><a href="{{ route('app.inbox') }}">0 unassigned</a></div>
            <div class="card"><small>Tickets</small><a href="#" data-toast="Tickets — coming soon.">0 unassigned</a></div>
            <div class="card"><small>Lyro AI Agent</small><a href="#" data-toast="Lyro — coming soon.">0 unanswered questions</a></div>
            <div class="card"><small>Flows</small><a href="{{ route('app.flows') }}">1 active Flow</a></div>
            <div class="card"><small>Live visitors</small><a href="#" data-toast="Customers — coming soon.">0 on your site</a></div>
        </div>

        <h2 class="sec">Performance</h2>
        <div class="card" style="padding:0">
            <div class="tabs">
                <div class="on">Interactions<b>0</b></div>
                <div>AI resolution rate<b>0%</b></div>
                <div>Sales assisted<b>0</b></div>
                <div>Leads acquired<b>0</b></div>
            </div>
            <div class="chart"></div>
        </div>
    </div>

    <aside class="side">
        <h3>Project status</h3>
        <div class="card">
            <p><b>Chat widget</b> <i class="bi bi-exclamation-circle bad"></i><br>Not installed<br>
                <a href="{{ route('app.settings') }}">Install chat widget</a></p>
            <p><b>Mailbox</b><br><a href="{{ route('app.settings') }}">Connect your mailbox</a></p>
            <p><b>Domains</b><br><a href="{{ route('app.settings') }}">Connect domain</a></p>
        </div>

        <h3>Current usage</h3>
        <div class="card">
            <p><b>Customer service</b><br>Billable conversations 0 / ∞</p>
            <div class="bar"></div>
            <p><b>Lyro AI Agent</b><br>AI conversations 0 / 50</p>
            <div class="bar"></div>
        </div>
    </aside>
</div>