<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Install the chat widget</h2>

        <div class="card">
            <label style="margin-top:0">Paste this snippet before &lt;/body&gt; on your site</label>
            <pre id="widget-snippet" style="background:#0B1226;color:#E7ECF5;border:1px solid var(--line);padding:16px;border-radius:10px;overflow:auto">{{ $snippet }}</pre>
            <button type="button" class="btn pri" onclick="navigator.clipboard.writeText(document.getElementById('widget-snippet').innerText); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy code', 1500)">Copy code</button>
            <a class="btn" href="mailto:?subject=Install%20our%20chat%20widget&body={{ urlencode('Please add this snippet before </body> on our site:'."\n\n".$snippet) }}">Send to developer</a>
        </div>

        <h2 class="sec">Platform guides</h2>
        <div class="tpl">
            <div class="card"><i class="bi bi-code-slash"></i><p><b>Custom HTML</b></p></div>
            <div class="card"><i class="bi bi-wordpress"></i><p><b>WordPress</b></p></div>
            <div class="card"><i class="bi bi-shop"></i><p><b>Shopify</b></p></div>
        </div>
    </div>
</div>
