@extends('layouts.site')
@section('title', 'Page not found — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header" style="padding:6rem 0"><div class="container"><div class="notfound">404</div><h1>This page drifted away</h1><p>The page you are looking for does not exist or has moved.</p><div class="hero-actions"><a href="{{ route('home') }}" class="btn btn-cobalt">Back to home</a><a href="{{ url('/help-center') }}" class="btn btn-outline-ink">Visit the Help Center</a></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
