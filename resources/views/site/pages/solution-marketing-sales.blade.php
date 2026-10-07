@extends('layouts.site')
@section('title', 'Marketing & Sales — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Solutions</div><h1>Turn visitors into pipeline</h1><p>Start conversations at the moment of intent and know exactly what each one earned.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">The challenge, and how Loop helps</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-person-plus"></i></div><h3>Lost visitors</h3><p>Engage high-intent visitors before they leave.</p></div><div class="tile"><div class="ico"><i class="bi bi-funnel"></i></div><h3>Cold leads</h3><p>Qualify in chat and pass hot leads to sales instantly.</p></div><div class="tile"><div class="ico"><i class="bi bi-graph-up"></i></div><h3>Unclear ROI</h3><p>Attribute revenue to conversations and flows.</p></div></div></div></section><section class="sec"><div class="container"><h2 class="h2s">Popular use cases</h2><div style="height:1rem"></div><div class="glass"><ul style="margin:0;padding-left:1.1rem;color:var(--ink-soft);line-height:2"><li>Lead capture and qualification</li><li>Meeting booking</li><li>Product and plan recommendations</li><li>Campaign follow-ups</li></ul></div></div></section><section class="sec"><div class="container"><div class="glass" style="text-align:center"><div class="metric">2.4×</div><p class="quote" style="margin:.75rem 0 0">more qualified leads from the same traffic</p><small style="color:var(--ink-soft)">Illustrative example</small></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
