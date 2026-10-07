@extends('layouts.site')
@section('title', 'Security — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Security</div><h1>Built to pass your security review</h1><p>How we protect your data and your customers' data.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Our practices</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-lock"></i></div><h3>Encryption</h3><p>Data encrypted in transit and at rest.</p></div><div class="tile"><div class="ico"><i class="bi bi-key"></i></div><h3>Access control</h3><p>Roles, permissions and single sign-on for teams.</p></div><div class="tile"><div class="ico"><i class="bi bi-journal-check"></i></div><h3>Audit logs</h3><p>Track sensitive actions across your workspace.</p></div><div class="tile"><div class="ico"><i class="bi bi-cloud-check"></i></div><h3>Reliable infrastructure</h3><p>Redundant hosting with continuous monitoring.</p></div><div class="tile"><div class="ico"><i class="bi bi-eye-slash"></i></div><h3>Privacy by design</h3><p>Collect only what is needed, delete on request.</p></div><div class="tile"><div class="ico"><i class="bi bi-bug"></i></div><h3>Responsible disclosure</h3><p>Report vulnerabilities and we respond quickly.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
