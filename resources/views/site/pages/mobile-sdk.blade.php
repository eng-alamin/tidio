@extends('layouts.site')
@section('title', 'Mobile SDK — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Mobile SDK</div><h1>Chat inside your app</h1><p>Add live chat and the AI agent to iOS and Android apps.</p></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Highlights</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-phone"></i></div><h3>Native UI</h3><p>Looks right on every device.</p></div><div class="tile"><div class="ico"><i class="bi bi-bell"></i></div><h3>Push notifications</h3><p>Reply alerts even when the app is closed.</p></div><div class="tile"><div class="ico"><i class="bi bi-person-badge"></i></div><h3>Identify users</h3><p>Pass user data for personalized replies.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
