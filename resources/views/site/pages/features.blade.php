@extends('layouts.site')
@section('title', 'Features — Live chat & support platform')

@push('styles')
<style>

    :root {
        --ink: #14213D;
        --ink-soft: #4A5578;
        --paper: #FBF9F4;
        --cobalt: #2B5FE2;
        --cobalt-dark: #1E46B3;
        --citrus: #FFC93C;
        --mist: #E7ECF5;
        --ok-green: #1FAA59;
        --line: #DCE2EF;

        --font-display: 'Fraunces', Georgia, serif;
        --font-body: 'Inter', system-ui, sans-serif;
    }

    body {
        background: var(--paper);
        color: var(--ink);
        font-family: var(--font-body);
    }

    /* Nav (shared) */
    .site-nav { padding: 1.25rem 0; border-bottom: 1px solid var(--line); }
    .site-nav .brand {
        font-family: var(--font-display);
        font-weight: 600;
        font-size: 1.3rem;
        color: var(--ink);
        text-decoration: none;
    }
    .site-nav a.nav-link { color: var(--ink-soft); font-weight: 500; font-size: 0.95rem; }
    .site-nav a.nav-link:hover { color: var(--ink); }
    .btn-cobalt {
        background: var(--cobalt); color: #fff; font-weight: 600;
        padding: 0.7rem 1.5rem; border-radius: 8px; border: none;
    }
    .btn-cobalt:hover { background: var(--cobalt-dark); color: #fff; }
    .btn-outline-ink {
        background: transparent; color: var(--ink); font-weight: 600;
        padding: 0.7rem 1.5rem; border-radius: 8px; border: 1.5px solid var(--line);
    }
    .btn-outline-ink:hover { border-color: var(--ink); }

    /* Header */
    .features-header { padding: 5rem 0 3rem; text-align: center; }
    .features-header .eyebrow { font-weight: 600; font-size: 0.95rem; color: var(--cobalt); margin-bottom: 1rem; }
    .features-header h1 {
        font-family: var(--font-display);
        font-weight: 500;
        font-size: clamp(2.2rem, 4vw, 3rem);
        max-width: 18ch;
        margin: 0 auto 1.25rem;
    }
    .features-header p { color: var(--ink-soft); font-size: 1.1rem; max-width: 46ch; margin: 0 auto; }

    /* Feature blocks - alternating image/text */
    .feature-block { padding: 4rem 0; }
    .feature-block:not(:last-child) { border-bottom: 1px solid var(--line); }
    .feature-block .feature-icon {
        width: 52px; height: 52px;
        background: var(--mist);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: var(--cobalt);
        margin-bottom: 1.5rem;
    }
    .feature-block h2 {
        font-family: var(--font-display);
        font-weight: 500;
        font-size: clamp(1.6rem, 3vw, 2.1rem);
        margin-bottom: 1rem;
    }
    .feature-block p.desc { color: var(--ink-soft); font-size: 1.05rem; line-height: 1.65; max-width: 46ch; }
    .feature-block ul.mini-list { list-style: none; padding: 0; margin: 1.5rem 0 0; }
    .feature-block ul.mini-list li {
        display: flex; gap: 0.6rem; padding: 0.4rem 0; font-size: 0.95rem; color: var(--ink);
    }
    .feature-block ul.mini-list li .bi { color: var(--ok-green); }

    .feature-visual {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1.75rem;
        box-shadow: 0 20px 40px -28px rgba(20,33,61,0.25);
        height: 100%;
    }
    .feature-visual .bubble {
        padding: 0.6rem 0.9rem; border-radius: 12px; font-size: 0.88rem; max-width: 85%; margin-bottom: 0.6rem;
    }
    .feature-visual .bubble--in { background: var(--mist); color: var(--ink); border-bottom-left-radius: 4px; }
    .feature-visual .bubble--out { background: var(--cobalt); color: #fff; margin-left: auto; border-bottom-right-radius: 4px; }
    .feature-visual .visual-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.6rem 0; border-top: 1px solid var(--line); font-size: 0.9rem;
    }
    .feature-visual .visual-row:first-child { border-top: none; }
    .status-pill {
        display: inline-flex; align-items: center; gap: 0.35rem;
        font-size: 0.78rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 999px;
    }
    .status-pill.online { background: rgba(31,170,89,0.12); color: var(--ok-green); }
    .status-pill.routed { background: rgba(43,95,226,0.1); color: var(--cobalt); }

    /* Final CTA */
    .final-cta { padding: 5rem 0 6rem; text-align: center; }
    .final-cta h2 {
        font-family: var(--font-display); font-weight: 500;
        font-size: clamp(1.8rem, 3.5vw, 2.4rem); max-width: 20ch; margin: 0 auto 1.75rem;
    }

    @media (max-width: 767.98px) {
        .features-header { padding: 3rem 0 2rem; }
        .feature-block .row > div:last-child { margin-top: 2rem; }
    }


/* ---- Modern polish: motion, depth, smoothness ---- */
html { scroll-behavior: smooth; }

.site-nav {
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(251, 249, 244, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: box-shadow 0.25s ease;
}
.site-nav.scrolled { box-shadow: 0 4px 20px -12px rgba(20,33,61,0.15); }

.site-nav a.nav-link { position: relative; transition: color 0.2s ease; }
.site-nav a.nav-link::after {
    content: "";
    position: absolute;
    left: 0; bottom: -4px;
    width: 0; height: 2px;
    background: var(--cobalt);
    transition: width 0.25s ease;
}
.site-nav a.nav-link:hover::after { width: 100%; }

.btn-cobalt, .btn-outline-ink, .btn-social {
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
}
.btn-cobalt:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -10px rgba(43, 95, 226, 0.45);
}
.btn-outline-ink:hover, .btn-social:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -12px rgba(20,33,61,0.2);
}
.btn-cobalt:active, .btn-outline-ink:active { transform: translateY(0); }

.price-card, .feature-visual, .value-card, .chat-mock,
.auth-card, .contact-form-card, .logo-strip .wordmark {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.price-card:hover, .value-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 24px 44px -24px rgba(20,33,61,0.28);
}
.price-card--featured:hover { transform: translateY(-8px); }
.feature-visual:hover, .chat-mock:hover {
    transform: translateY(-4px);
    box-shadow: 0 28px 50px -26px rgba(20,33,61,0.3);
}
.logo-strip .wordmark { display: inline-block; }
.logo-strip .wordmark:hover { opacity: 0.9 !important; transform: translateY(-2px); }

/* Scroll reveal */
.reveal-el {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.7s cubic-bezier(.21,.6,.35,1), transform 0.7s cubic-bezier(.21,.6,.35,1);
}
.reveal-el.is-visible { opacity: 1; transform: translateY(0); }

@media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    .reveal-el { opacity: 1; transform: none; transition: none; }
    .btn-cobalt:hover, .btn-outline-ink:hover, .btn-social:hover,
    .price-card:hover, .value-card:hover, .feature-visual:hover, .chat-mock:hover {
        transform: none;
    }
}

</style>
@endpush

@section('content')
<!-- Header -->
<section class="features-header">
    <div class="container">
        <div class="eyebrow">Features</div>
        <h1>Everything your support team needs, in one inbox</h1>
        <p>From the first hello to the last follow-up, built to make every reply faster and every handoff smoother.</p>
    </div>
</section>

<!-- Feature: Shared inbox -->
<section class="feature-block">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="feature-icon"><i class="bi bi-inboxes"></i></div>
                <h2>One inbox for every channel</h2>
                <p class="desc">Chat, email, and social messages land in a single shared inbox, so nothing slips through and no two people answer the same question twice.</p>
                <ul class="mini-list">
                    <li><i class="bi bi-check-lg"></i> Live chat widget for your site</li>
                    <li><i class="bi bi-check-lg"></i> Email forwarding & threading</li>
                    <li><i class="bi bi-check-lg"></i> Assign conversations to teammates</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="feature-visual">
                    <div class="bubble bubble--in">Can I change my shipping address?</div>
                    <div class="bubble bubble--out">Sure, I've updated it to your new one.</div>
                    <div class="visual-row"><span>Assigned to</span> <strong>Maya R.</strong></div>
                    <div class="visual-row"><span>Channel</span> <span class="status-pill online">Live chat</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature: Answer bot -->
<section class="feature-block">
    <div class="container">
        <div class="row align-items-center flex-lg-row-reverse">
            <div class="col-lg-6">
                <div class="feature-icon"><i class="bi bi-robot"></i></div>
                <h2>An automation that knows when to step back</h2>
                <p class="desc">The answer bot handles order status, shipping, and FAQ questions on its own, and hands off to a person the moment a question needs a human touch.</p>
                <ul class="mini-list">
                    <li><i class="bi bi-check-lg"></i> Trained on your help center content</li>
                    <li><i class="bi bi-check-lg"></i> Confidence-based handoff to agents</li>
                    <li><i class="bi bi-check-lg"></i> Multi-language replies</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="feature-visual">
                    <div class="visual-row"><span>Order #4821 status</span> <span class="status-pill online">Resolved by bot</span></div>
                    <div class="visual-row"><span>Return policy question</span> <span class="status-pill online">Resolved by bot</span></div>
                    <div class="visual-row"><span>Billing dispute</span> <span class="status-pill routed">Routed to agent</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature: Visitor insight -->
<section class="feature-block">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="feature-icon"><i class="bi bi-person-lines-fill"></i></div>
                <h2>Context before you even say hi</h2>
                <p class="desc">See what page a visitor is on, what's in their cart, and their past orders while you're chatting — no more asking them to repeat themselves.</p>
                <ul class="mini-list">
                    <li><i class="bi bi-check-lg"></i> Live page & cart visibility</li>
                    <li><i class="bi bi-check-lg"></i> Order history at a glance</li>
                    <li><i class="bi bi-check-lg"></i> Past conversation timeline</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="feature-visual">
                    <div class="visual-row"><span>Currently viewing</span> <strong>Checkout page</strong></div>
                    <div class="visual-row"><span>Cart value</span> <strong>$84.00</strong></div>
                    <div class="visual-row"><span>Past orders</span> <strong>3</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature: Team routing -->
<section class="feature-block">
    <div class="container">
        <div class="row align-items-center flex-lg-row-reverse">
            <div class="col-lg-6">
                <div class="feature-icon"><i class="bi bi-signpost-split"></i></div>
                <h2>Right question, right team, every time</h2>
                <p class="desc">Route billing questions to billing and shipping questions to fulfillment automatically, based on rules you set once.</p>
                <ul class="mini-list">
                    <li><i class="bi bi-check-lg"></i> Keyword & topic-based rules</li>
                    <li><i class="bi bi-check-lg"></i> Load balancing across agents</li>
                    <li><i class="bi bi-check-lg"></i> Business hours & fallback queues</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="feature-visual">
                    <div class="visual-row"><span>"refund" mentioned</span> <span class="status-pill routed">→ Billing team</span></div>
                    <div class="visual-row"><span>"tracking" mentioned</span> <span class="status-pill routed">→ Fulfillment team</span></div>
                    <div class="visual-row"><span>No match</span> <span class="status-pill routed">→ General queue</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="sec" data-injected><div class="container"><h2 class="h2s">All features</h2><p class="sub2">Everything in one platform.</p><div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))"><div class="tile"><div class="ico"><i class="bi bi-chat-dots"></i></div><h3>Live typing preview</h3><p>See replies forming before they are sent.</p></div><div class="tile"><div class="ico"><i class="bi bi-magic"></i></div><h3>AI reply assistant</h3><p>Draft and polish replies in one click.</p></div><div class="tile"><div class="ico"><i class="bi bi-ticket-perforated"></i></div><h3>Ticketing workflows</h3><p>Priorities, tags and smart views.</p></div><div class="tile"><div class="ico"><i class="bi bi-people"></i></div><h3>Live visitor list</h3><p>Start chats before visitors leave.</p></div><div class="tile"><div class="ico"><i class="bi bi-inboxes"></i></div><h3>Multichannel inbox</h3><p>Chat, email and social in one place.</p></div><div class="tile"><div class="ico"><i class="bi bi-signpost-split"></i></div><h3>Automatic assignment</h3><p>Route to the right operator.</p></div><div class="tile"><div class="ico"><i class="bi bi-diagram-3"></i></div><h3>Visual automation builder</h3><p>40+ templates, no code.</p></div><div class="tile"><div class="ico"><i class="bi bi-cart-x"></i></div><h3>Save abandoned carts</h3><p>Bring shoppers back to checkout.</p></div><div class="tile"><div class="ico"><i class="bi bi-box-seam"></i></div><h3>Order management</h3><p>Check and manage orders in chat.</p></div><div class="tile"><div class="ico"><i class="bi bi-bar-chart-line"></i></div><h3>Sales analytics</h3><p>Revenue attributed to conversations.</p></div><div class="tile"><div class="ico"><i class="bi bi-palette"></i></div><h3>Full customization</h3><p>Match your brand, remove branding.</p></div><div class="tile"><div class="ico"><i class="bi bi-plug"></i></div><h3>OpenAPI and integrations</h3><p>Connect to your stack.</p></div></div></div></section>
<section class="final-cta">
    <div class="container">
        <h2>See it running on your own site</h2>
        <a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a>
    </div>
</section>
@endsection
