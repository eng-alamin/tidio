@extends('layouts.site')
@section('title', 'Education — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Solutions</div><h1>Answer students and families at any hour</h1><p>Handle admissions, course and support questions without a queue.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">The challenge, and how Loop helps</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-question-circle"></i></div><h3>Repeated questions</h3><p>Answer FAQs on deadlines and fees.</p></div><div class="tile"><div class="ico"><i class="bi bi-translate"></i></div><h3>Many languages</h3><p>Support international applicants.</p></div><div class="tile"><div class="ico"><i class="bi bi-calendar-event"></i></div><h3>Peak seasons</h3><p>Scale during enrollment rushes.</p></div></div></div></section><section class="sec"><div class="container"><h2 class="h2s">Popular use cases</h2><div style="height:1rem"></div><div class="glass"><ul style="margin:0;padding-left:1.1rem;color:var(--ink-soft);line-height:2"><li>Admissions inquiries</li><li>Course information</li><li>Student support</li><li>Event registration</li></ul></div></div></section><section class="sec"><div class="container"><div class="glass" style="text-align:center"><div class="metric">3×</div><p class="quote" style="margin:.75rem 0 0">faster response to guest questions</p><small style="color:var(--ink-soft)">Illustrative example</small></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
