@extends('layouts.site')
@section('title', 'Custom AI Agents — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Custom AI agents</div><h1>Tell us what you need, we design an agent around it</h1><p>For workflows that go beyond a standard setup.</p><div class="hero-actions"><a href="{{ url('/contact-sales') }}" class="btn btn-cobalt">Talk to us</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">How we work</h2><div style="height:1rem"></div><div class="tile-grid"><div class="tile"><div class="step-num">1</div><h3>Discovery</h3><p>We map your conversations, systems and goals.</p></div><div class="tile"><div class="step-num">2</div><h3>Design and build</h3><p>We create an agent with the actions and guardrails you need.</p></div><div class="tile"><div class="step-num">3</div><h3>Launch and tune</h3><p>We measure results and keep improving.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
