@extends('layouts.site')
@section('title', 'Customer Service — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Solutions</div><h1>Fewer tickets, happier customers</h1><p>Let AI clear the repetitive questions and give your team the context to handle the rest.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">The challenge, and how Loop helps</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-clock"></i></div><h3>Slow replies</h3><p>Instant answers on common questions, 24/7.</p></div><div class="tile"><div class="ico"><i class="bi bi-shuffle"></i></div><h3>Scattered channels</h3><p>One inbox and one customer history.</p></div><div class="tile"><div class="ico"><i class="bi bi-emoji-frown"></i></div><h3>Burned-out agents</h3><p>Agents focus on the conversations that need judgment.</p></div></div></div></section><section class="sec"><div class="container"><h2 class="h2s">Popular use cases</h2><div style="height:1rem"></div><div class="glass"><ul style="margin:0;padding-left:1.1rem;color:var(--ink-soft);line-height:2"><li>Order status and returns questions</li><li>Password and account help</li><li>After-hours coverage</li><li>Multilingual support without extra hires</li></ul></div></div></section><section class="sec"><div class="container"><div class="glass" style="text-align:center"><div class="metric">68%</div><p class="quote" style="margin:.75rem 0 0">of repetitive questions handled without an agent</p><small style="color:var(--ink-soft)">Illustrative example</small></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
