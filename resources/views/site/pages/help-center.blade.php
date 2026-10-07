@extends('layouts.site')
@section('title', 'Help Center — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Help Center</div><h1>How can we help?</h1><p>Guides, answers and quick fixes for every part of Loop.</p><div class="search-box"><i class="bi bi-search"></i><input type="search" placeholder="Search articles" aria-label="Search articles"></div></div></section>
<section class="sec"><div class="container"><div class="tile-grid"><a class="tile" href="#"><div class="ico"><i class="bi bi-rocket-takeoff"></i></div><h3>Getting started</h3><p>Install the widget and reply to your first chat.</p></a><a class="tile" href="#"><div class="ico"><i class="bi bi-robot"></i></div><h3>Answer bot</h3><p>Train your bot and set handoff rules.</p></a><a class="tile" href="#"><div class="ico"><i class="bi bi-inboxes"></i></div><h3>Shared inbox</h3><p>Assign, tag and resolve conversations as a team.</p></a><a class="tile" href="#"><div class="ico"><i class="bi bi-plug"></i></div><h3>Integrations</h3><p>Connect stores, CRMs and other tools.</p></a><a class="tile" href="#"><div class="ico"><i class="bi bi-credit-card-2-front"></i></div><h3>Billing & plans</h3><p>Invoices, upgrades and seats.</p></a><a class="tile" href="#"><div class="ico"><i class="bi bi-shield-check"></i></div><h3>Security & privacy</h3><p>Data handling, roles and compliance.</p></a></div><h2 style="font-family:var(--font-display);font-weight:500;font-size:1.8rem;margin:3.5rem 0 .5rem">Popular articles</h2><div><a class="row-item" href="#"><h3>How do I add the chat widget to my site?</h3><i class="bi bi-arrow-right"></i></a><a class="row-item" href="#"><h3>How do I invite teammates and set roles?</h3><i class="bi bi-arrow-right"></i></a><a class="row-item" href="#"><h3>How does the answer bot hand off to a person?</h3><i class="bi bi-arrow-right"></i></a><a class="row-item" href="#"><h3>Can I change my plan mid-month?</h3><i class="bi bi-arrow-right"></i></a></div></div></section>
<section class="final-cta"><div class="container"><h2>Your next customer is waiting. Let's not keep them.</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a></div></section>
@endsection
