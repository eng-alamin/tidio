@extends('layouts.site')
@section('title', 'Trust Center — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Trust</div><h1>Transparency you can verify</h1><p>Certifications, policies and sub-processors in one place.</p></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Compliance</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-patch-check"></i></div><h3>SOC 2 Type 2</h3><p>Independently audited controls.</p></div><div class="tile"><div class="ico"><i class="bi bi-shield-check"></i></div><h3>GDPR</h3><p>Data processing agreements and EU-ready practices.</p></div><div class="tile"><div class="ico"><i class="bi bi-shield"></i></div><h3>CCPA</h3><p>Consumer privacy rights supported.</p></div><div class="tile"><div class="ico"><i class="bi bi-cpu"></i></div><h3>AI governance</h3><p>Guardrails and audit logs for AI conversations.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
