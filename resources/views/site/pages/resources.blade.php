@extends('layouts.site')
@section('title', 'Resources — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Resources</div><h1>Learn, compare and get inspired</h1><p>Everything we publish to help you deliver better conversations.</p></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Explore</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-star"></i></div><h3>Customer stories</h3><p>Real results from growing teams.</p></div><div class="tile"><div class="ico"><i class="bi bi-book"></i></div><h3>Ebooks</h3><p>Guides you can put to work today.</p></div><div class="tile"><div class="ico"><i class="bi bi-trophy"></i></div><h3>Compare</h3><p>See how Loop stacks up.</p></div><div class="tile"><div class="ico"><i class="bi bi-newspaper"></i></div><h3>Blog</h3><p>Playbooks and product news.</p></div><div class="tile"><div class="ico"><i class="bi bi-life-preserver"></i></div><h3>Help Center</h3><p>Step-by-step answers.</p></div><div class="tile"><div class="ico"><i class="bi bi-calculator"></i></div><h3>ROI calculator</h3><p>Estimate your savings.</p></div><div class="tile"><div class="ico"><i class="bi bi-play-circle"></i></div><h3>Watch demo</h3><p>A short product tour.</p></div><div class="tile"><div class="ico"><i class="bi bi-chat-square-text"></i></div><h3>AI playground</h3><p>Try the agent yourself.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
