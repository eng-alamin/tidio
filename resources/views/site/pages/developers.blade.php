@extends('layouts.site')
@section('title', 'Developers — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Developers</div><h1>Build on Loop</h1><p>Embed the widget, use the API and connect your own systems.</p></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Install the widget</h2><div style="height:1rem"></div><pre class="code">&lt;script src="https://cdn.loop.app/widget.js" data-key="YOUR_KEY" async&gt;&lt;/script&gt;</pre></div></section><section class="sec"><div class="container"><h2 class="h2s">Building blocks</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-braces"></i></div><h3>REST API</h3><p>Contacts, conversations and tickets.</p></div><div class="tile"><div class="ico"><i class="bi bi-bell"></i></div><h3>Webhooks</h3><p>Get events in real time.</p></div><div class="tile"><div class="ico"><i class="bi bi-window"></i></div><h3>Widget API</h3><p>Open, hide and pre-fill the chat.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
