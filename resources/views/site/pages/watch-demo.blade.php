@extends('layouts.site')
@section('title', 'Watch Demo — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Demo</div><h1>See Loop in action</h1><p>A short tour of the inbox, AI agent and flows.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container" style="max-width:860px"><div class="glass demo-video"><i class="bi bi-play-circle"></i></div></div></section><section class="sec"><div class="container"><h2 class="h2s">Chapters</h2><div style="height:1rem"></div><a class="row-item" href="{{ url('/help-desk') }}"><div><h3>Shared inbox</h3><small>0:00</small></div><i class="bi bi-arrow-right"></i></a><a class="row-item" href="{{ url('/ai-agent') }}"><div><h3>AI agent</h3><small>1:10</small></div><i class="bi bi-arrow-right"></i></a><a class="row-item" href="{{ url('/flows') }}"><div><h3>Flows</h3><small>2:30</small></div><i class="bi bi-arrow-right"></i></a></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
