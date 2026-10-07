@extends('layouts.site')
@section('title', 'Ebooks — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Ebooks</div><h1>Guides to better conversations</h1><p>Free downloads for support, sales and marketing teams.</p></div></section>
<section class="sec"><div class="container"><h2 class="h2s">Featured</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-book"></i></div><h3>The support playbook</h3><p>Build a support operation that scales.</p></div><div class="tile"><div class="ico"><i class="bi bi-cart-check"></i></div><h3>Ecommerce chat guide</h3><p>Turn chats into checkout.</p></div><div class="tile"><div class="ico"><i class="bi bi-robot"></i></div><h3>AI agents in practice</h3><p>Roll out an agent without surprises.</p></div></div></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
