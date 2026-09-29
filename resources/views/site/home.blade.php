@extends('layouts.site')

@section('content')

<div class="public-home">

    <!-- Hero -->
    <section class="home-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="eyebrow">Live chat & AI customer support</div>
                    <h1>See what your last 100 chats actually earned you.</h1>
                    <p class="lead-copy">
                        Loop's AI resolves the routine questions instantly and turns the rest
                        into tracked sales, leads, and bookings — so you always know what a
                        conversation was worth.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a>
                        <a href="#product-demo" class="btn btn-outline-ink">Watch a 2-min demo</a>
                    </div>
                    <div class="trust-line">
                        Free plan forever &middot; Paid plans from $29/mo &middot; Cancel anytime
                    </div>
                </div>

                <div class="col-lg-6 mt-5 mt-lg-0">
                    <div class="chat-mock">
                        <div class="chat-mock__header">
                            <span class="chat-mock__status-dot"></span>
                            <span style="font-weight:600; font-size:0.95rem;">Support</span>
                        </div>
                        <div class="chat-mock__body">
                            <div class="bubble bubble--in">Hi, does my order ship internationally?</div>
                            <div class="bubble bubble--out">Yes! We ship to 40+ countries — I can check your address now.</div>
                            <div class="chat-mock__reply-time">Replied in 8 seconds</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Logo strip -->
    <section class="logo-strip">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-6 col-md-2"><span class="wordmark">Norra</span></div>
                <div class="col-6 col-md-2"><span class="wordmark">Fieldkit</span></div>
                <div class="col-6 col-md-2"><span class="wordmark">Havenly</span></div>
                <div class="col-6 col-md-2"><span class="wordmark">Aldergrove</span></div>
                <div class="col-6 col-md-2"><span class="wordmark">Cursive</span></div>
                <div class="col-6 col-md-2"><span class="wordmark">Portside</span></div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container">
            <div class="features-intro mb-4">
                <h2>Everything a support team needs, nothing it doesn't</h2>
            </div>

            <div class="feature-row">
                <div class="feature-row__num">01</div>
                <div>
                    <h3>Shared inbox</h3>
                    <p>Every chat, email, and social message lands in one place, so nothing gets answered twice or missed entirely.</p>
                </div>
            </div>
            <div class="feature-row">
                <div class="feature-row__num">02</div>
                <div>
                    <h3>Answer bot</h3>
                    <p>Handles order status, shipping, and FAQ questions on its own, and hands off to a person the moment it's unsure.</p>
                </div>
            </div>
            <div class="feature-row">
                <div class="feature-row__num">03</div>
                <div>
                    <h3>Visitor insight</h3>
                    <p>See what page a visitor is on and what's in their cart while you're chatting, so you're never asking them to repeat themselves.</p>
                </div>
            </div>
            <div class="feature-row">
                <div class="feature-row__num">04</div>
                <div>
                    <h3>Team routing</h3>
                    <p>Send billing questions to billing, and shipping questions to fulfillment, automatically, based on rules you set.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="pb-5">
        <div class="container">
            <div class="stats-band">
                <div class="row text-center g-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-num">92%</div>
                        <div class="stat-label">Customer satisfaction</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-num">11s</div>
                        <div class="stat-label">Median first reply</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-num">2,400+</div>
                        <div class="stat-label">Teams onboard</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-num">40</div>
                        <div class="stat-label">Countries served</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Security -->
    <section class="pb-5">
        <div class="container text-center">
            <p class="text-uppercase" style="font-size:0.85rem; font-weight:600; color:var(--ink-soft); letter-spacing:0.04em;">Built to pass your security review</p>
            <div class="d-flex flex-wrap justify-content-center gap-4 mt-3">
                <span class="badge-pill"><i class="bi bi-patch-check-fill" style="color:var(--ok-green);"></i> SOC 2 Type 2</span>
                <span class="badge-pill"><i class="bi bi-patch-check-fill" style="color:var(--ok-green);"></i> GDPR</span>
                <span class="badge-pill"><i class="bi bi-patch-check-fill" style="color:var(--ok-green);"></i> CCPA</span>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <!-- data-injected --><section class="sec" data-injected><div class="container"><h2 class="h2s">Three ways every conversation pays off</h2><p class="sub2">Sell, resolve and scale, from a free plan to enterprise.</p><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-cart-check"></i></div><h3>They sell, not just answer</h3><p>Product recommendations, cart recovery and lead capture mid-conversation, with attribution.</p></div><div class="tile"><div class="ico"><i class="bi bi-robot"></i></div><h3>AI you don't have to babysit</h3><p>Answers only from your data, says "I don't know", and hands off with full context.</p></div><div class="tile"><div class="ico"><i class="bi bi-graph-up-arrow"></i></div><h3>Grows from free to enterprise</h3><p>Start free, then add custom agents, priority SLAs and 120+ integrations.</p></div></div><div style="height:2rem"></div><div class="stat3"><div><b>67%</b><span>of chats resolved instantly*</span></div><div><b>120+</b><span>integrations</span></div><div><b>4.8</b><span>average review score*</span></div></div><p style="color:var(--ink-soft);font-size:.8rem">*Illustrative figures for this demo site.</p></div></section>
<section class="final-cta">
        <div class="container">
            <h2>Your next customer is waiting. Let's not keep them.</h2>
            <a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a>
        </div>
    </section>

</div>

@endsection

@push('page_styles_bottom')
<style>
    .badge-pill {
        display: inline-flex; align-items: center; gap: 0.4rem;
        border: 1px solid var(--line); border-radius: 999px;
        padding: 0.4rem 0.9rem; font-size: 0.85rem; font-weight: 600; color: var(--ink);
        background: #fff;
    }
</style>
@endpush
