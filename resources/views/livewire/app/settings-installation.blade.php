<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Install the chat widget</h2>

        <div class="card">
            <label style="margin-top:0">1. Website domain</label>
            <p style="color:var(--ink-soft,#4A5578);margin:4px 0 10px;font-size:14px">The widget only works on this domain and its subdomains. Chats started anywhere else are refused.</p>
            <form wire:submit="saveDomain" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start">
                <input class="f" type="text" wire:model="domain" placeholder="example.com" autocomplete="off" style="flex:1;min-width:220px;max-width:360px" aria-label="Website domain">
                <button type="submit" class="btn pri" wire:loading.attr="disabled" wire:target="saveDomain">Save domain</button>
            </form>
            @error('domain') <small style="color:var(--bad);display:block;margin-top:6px">{{ $message }}</small> @enderror
        </div>

        <div class="card" style="margin-top:14px">
            <label style="margin-top:0">2. Paste this snippet before &lt;/body&gt; on your site</label>
            <pre id="widget-snippet" style="background:#0B1226;color:#E7ECF5;border:1px solid var(--line);padding:16px;border-radius:10px;overflow:auto">{{ $snippet }}</pre>
            <button type="button" class="btn pri" onclick="navigator.clipboard.writeText(document.getElementById('widget-snippet').innerText); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy code', 1500)">Copy code</button>
            <a class="btn" href="mailto:?subject=Install%20our%20chat%20widget&body={{ urlencode('Please add this snippet before </body> on our site:'."\n\n".$snippet) }}">Send to developer</a>
            <a class="btn" href="{{ route('app.widget.preview') }}" target="_blank" rel="noopener">Try it on a preview page</a>
        </div>

        <div class="card" style="margin-top:14px" @unless ($installed) wire:poll.5s="refreshStatus" @endunless>
            <label style="margin-top:0">3. Status</label>
            @if ($installed)
                <p style="margin:8px 0 0"><span class="pill ok">Installed</span> <span style="color:var(--ink-soft,#4A5578);font-size:14px">First seen on your site {{ $installed_at }}.</span></p>
            @else
                <p style="margin:8px 0 0"><span class="pill">Waiting for first visit</span> <span style="color:var(--ink-soft,#4A5578);font-size:14px">Open a page of your site that has the snippet; this updates by itself.</span></p>
            @endif
        </div>

        <h2 class="sec">Platform guides</h2>
        <div class="tpl">
            <div class="card"><i class="bi bi-code-slash"></i><p><b>Custom HTML</b></p></div>
            <div class="card"><i class="bi bi-wordpress"></i><p><b>WordPress</b></p></div>
            <div class="card"><i class="bi bi-shop"></i><p><b>Shopify</b></p></div>
        </div>
    </div>
</div>
