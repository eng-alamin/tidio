@extends('layouts.site')
@section('title', 'Premium Plan — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Premium plan</div><h1>Grow faster with done-for-you automation</h1><p>Hands-on setup, optimization strategy and custom limits for teams that want results without the build time.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Everything in Growth, plus</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-tools"></i></div><h3>Done-for-you setup</h3><p>Our specialists build and tune your flows and agent.</p></div><div class="tile"><div class="ico"><i class="bi bi-graph-up-arrow"></i></div><h3>Optimization strategy</h3><p>Regular reviews with concrete ways to raise revenue and resolution.</p></div><div class="tile"><div class="ico"><i class="bi bi-sliders"></i></div><h3>Custom limits</h3><p>Volumes and seats sized to your business.</p></div><div class="tile"><div class="ico"><i class="bi bi-headset"></i></div><h3>Priority support</h3><p>Faster response times and a named contact.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
