@extends('layouts.site')
@section('title', 'Partner Program — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Partners</div><h1>Grow with Loop</h1><p>Agencies, developers and consultants earn recurring revenue helping clients deliver better conversations.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Choose your path</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-building"></i></div><h3>Agency partners</h3><p>Recurring commission on client accounts.</p></div><div class="tile"><div class="ico"><i class="bi bi-code-slash"></i></div><h3>Technology partners</h3><p>Build integrations and reach our users.</p></div><div class="tile"><div class="ico"><i class="bi bi-share"></i></div><h3>Referral partners</h3><p>Share Loop and earn a reward.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
