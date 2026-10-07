@extends('layouts.site')
@section('title', 'SaaS — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Solutions</div><h1>Onboard, support and convert in the product</h1><p>Guide trial users, deflect how-to questions and flag upgrade moments.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">The challenge, and how Loop helps</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-rocket"></i></div><h3>Trial drop-off</h3><p>Help new users reach value faster.</p></div><div class="tile"><div class="ico"><i class="bi bi-book"></i></div><h3>Docs overload</h3><p>Answer from your documentation.</p></div><div class="tile"><div class="ico"><i class="bi bi-arrow-up-circle"></i></div><h3>Upgrade timing</h3><p>Spot high-intent users.</p></div></div></div></section><section class="sec"><div class="container"><h2 class="h2s">Popular use cases</h2><div style="height:1rem"></div><div class="glass"><ul style="margin:0;padding-left:1.1rem;color:var(--ink-soft);line-height:2"><li>Trial onboarding</li><li>Docs-based answers</li><li>Bug triage</li><li>Expansion conversations</li></ul></div></div></section><section class="sec"><div class="container"><div class="glass" style="text-align:center"><div class="metric">3×</div><p class="quote" style="margin:.75rem 0 0">faster response to guest questions</p><small style="color:var(--ink-soft)">Illustrative example</small></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
