@extends('layouts.site')
@section('title', 'Ecommerce — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Solutions</div><h1>Sell more, support faster, in one chat</h1><p>Recommend products, recover carts and answer order questions.</p><div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a><a href="{{ url('/contact-sales') }}" class="btn btn-outline-ink">Contact sales</a></div></div></section>
<section class="sec"><div class="container"><h2 class="h2s">The challenge, and how Loop helps</h2><div style="height:1rem"></div><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-cart-x"></i></div><h3>Cart abandonment</h3><p>Nudge shoppers back to checkout.</p></div><div class="tile"><div class="ico"><i class="bi bi-box-seam"></i></div><h3>Order questions</h3><p>Give live order and shipping status.</p></div><div class="tile"><div class="ico"><i class="bi bi-tags"></i></div><h3>Product discovery</h3><p>Recommend from your live catalog.</p></div></div></div></section><section class="sec"><div class="container"><h2 class="h2s">Popular use cases</h2><div style="height:1rem"></div><div class="glass"><ul style="margin:0;padding-left:1.1rem;color:var(--ink-soft);line-height:2"><li>Cart recovery</li><li>Size and fit help</li><li>Order tracking</li><li>Discount offers</li></ul></div></div></section><section class="sec"><div class="container"><div class="glass" style="text-align:center"><div class="metric">3×</div><p class="quote" style="margin:.75rem 0 0">faster response to guest questions</p><small style="color:var(--ink-soft)">Illustrative example</small></div></div></section>
<section class="sec"><div class="container"><a class="tile glass" href="{{ url('/ai-agent-product-recommendations') }}" style="display:flex;align-items:center;justify-content:space-between;gap:1rem"><div><h3 style="margin:0">See product recommendations in action</h3><p>Try the AI agent on a sample catalog.</p></div><i class="bi bi-arrow-right" style="font-size:1.5rem"></i></a></div></section>
<section class="final-cta"><div class="container"><h2>What will your next 100 conversations earn?</h2><a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a><p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p></div></section>
@endsection
