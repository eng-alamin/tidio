<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Loop — Live chat & support platform')</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/glass-dark.css') }}">
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

    body.public-home {
        background: var(--paper);
        color: var(--ink);
        font-family: var(--font-body);
    }

    /* Nav */
    .site-nav {
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--line);
    }
    .site-nav .brand {
        font-family: var(--font-display);
        font-weight: 600;
        font-size: 1.3rem;
        color: var(--ink);
        text-decoration: none;
    }
    .site-nav a.nav-link {
        color: var(--ink-soft);
        font-weight: 500;
        font-size: 0.95rem;
    }
    .site-nav a.nav-link:hover { color: var(--ink); }
    .site-nav .btn-outline-ink {
        padding: 0.7rem 1.5rem;
    }
    .site-nav .btn-cobalt {
        padding: 0.7rem 1.5rem;
    }

    .home-hero {
        padding: 6rem 0 4rem;
        position: relative;
        overflow: hidden;
    }
    .home-hero::before {
        content: "";
        position: absolute;
        top: -180px; right: -160px;
        width: 520px; height: 520px;
        background: radial-gradient(circle, rgba(43,95,226,0.14) 0%, rgba(43,95,226,0) 70%);
        pointer-events: none;
        z-index: 0;
    }
    .home-hero .container { position: relative; z-index: 1; }

    .home-hero .eyebrow {
        font-family: var(--font-body);
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--cobalt);
        margin-bottom: 1rem;
    }

    .home-hero h1 {
        font-family: var(--font-display);
        font-weight: 500;
        font-size: clamp(2.4rem, 4vw, 3.6rem);
        line-height: 1.08;
        letter-spacing: -0.01em;
        color: var(--ink);
        max-width: 12ch;
        margin-bottom: 1.5rem;
    }

    .home-hero p.lead-copy {
        font-size: 1.15rem;
        line-height: 1.6;
        color: var(--ink-soft);
        max-width: 42ch;
        margin-bottom: 2rem;
    }

    .btn-cobalt {
        background: var(--cobalt);
        color: #fff;
        font-weight: 600;
        padding: 0.85rem 1.75rem;
        border-radius: 8px;
        border: none;
        transition: background 0.15s ease;
    }
    .btn-cobalt:hover { background: var(--cobalt-dark); color: #fff; }

    .btn-outline-ink {
        background: transparent;
        color: var(--ink);
        font-weight: 600;
        padding: 0.85rem 1.75rem;
        border-radius: 8px;
        border: 1.5px solid var(--line);
    }
    .btn-outline-ink:hover { border-color: var(--ink); color: var(--ink); }

    .trust-line {
        font-size: 0.9rem;
        color: var(--ink-soft);
        margin-top: 1.5rem;
    }
    .trust-line strong { color: var(--ink); }

    /* Chat widget mockup */
    .chat-mock {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--line);
        box-shadow: 0 24px 48px -20px rgba(20, 33, 61, 0.18);
        max-width: 360px;
        margin-left: auto;
        overflow: hidden;
    }
    .chat-mock__header {
        background: var(--cobalt);
        color: #fff;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .chat-mock__status-dot {
        width: 8px; height: 8px;
        background: var(--ok-green);
        border-radius: 50%;
        display: inline-block;
    }
    .chat-mock__body {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .bubble {
        padding: 0.6rem 0.9rem;
        border-radius: 12px;
        font-size: 0.9rem;
        line-height: 1.4;
        max-width: 80%;
    }
    .bubble--in {
        background: var(--mist);
        color: var(--ink);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
    .bubble--out {
        background: var(--cobalt);
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .chat-mock__reply-time {
        font-size: 0.75rem;
        color: var(--ink-soft);
        align-self: flex-start;
        padding-left: 0.25rem;
    }

    /* Logo strip */
    .logo-strip {
        padding: 2.5rem 0;
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
    }
    .logo-strip .wordmark {
        font-family: var(--font-display);
        font-size: 1.25rem;
        color: var(--ink-soft);
        opacity: 0.55;
    }

    /* Features */
    .features-section { padding: 5.5rem 0; }
    .features-intro h2 {
        font-family: var(--font-display);
        font-weight: 500;
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        max-width: 16ch;
        color: var(--ink);
    }
    .feature-row {
        display: flex;
        gap: 2.5rem;
        padding: 2.25rem 0;
        border-top: 1px solid var(--line);
        align-items: flex-start;
    }
    .feature-row:last-child { border-bottom: 1px solid var(--line); }
    .feature-row__num {
        font-family: var(--font-display);
        font-size: 1rem;
        color: var(--cobalt);
        width: 3rem;
        flex-shrink: 0;
        padding-top: 0.2rem;
    }
    .feature-row h3 {
        font-family: var(--font-body);
        font-weight: 600;
        font-size: 1.15rem;
        color: var(--ink);
        margin-bottom: 0.4rem;
    }
    .feature-row p {
        color: var(--ink-soft);
        max-width: 52ch;
        margin: 0;
    }

    /* Stats */
    .stats-band {
        background: var(--ink);
        color: #fff;
        padding: 3.5rem 0;
        border-radius: 20px;
    }
    .stats-band .stat-num {
        font-family: var(--font-display);
        font-size: 2.4rem;
        color: var(--citrus);
    }
    .stats-band .stat-label {
        color: #C6CCE0;
        font-size: 0.95rem;
    }

    /* Final CTA */
    .final-cta { padding: 6rem 0; text-align: center; }
    .final-cta h2 {
        font-family: var(--font-display);
        font-weight: 500;
        font-size: clamp(1.9rem, 3.5vw, 2.6rem);
        max-width: 20ch;
        margin: 0 auto 1.75rem;
        color: var(--ink);
    }

    @media (max-width: 767.98px) {
        .home-hero { padding: 3.5rem 0 2.5rem; }
        .chat-mock { margin: 2.5rem auto 0; }
        .feature-row { flex-direction: column; gap: 0.75rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .btn-cobalt, .btn-outline-ink { transition: none; }
    }
</style>
<style>
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
@stack('styles')
</head>
<body class="public-home @yield('body_class')">
<div class="sci-bg" aria-hidden="true"></div>
<!-- Nav -->
<nav class="site-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('home') }}" class="brand">Loop</a>
        <div class="d-none d-md-flex align-items-center gap-4">
            <div class="dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Product</a><div class="dropdown-menu"><a class="dropdown-item" href="live-chat.html"><span class="di"><i class="bi bi-chat-left-text"></i></span><span class="dt"><b>Live Chat</b><small>Talk to visitors in real time</small></span></a><a class="dropdown-item" href="ai-agent.html"><span class="di"><i class="bi bi-stars"></i></span><span class="dt"><b>AI Agent</b><small>Answers and sells from your data</small></span></a><a class="dropdown-item" href="help-desk.html"><span class="di"><i class="bi bi-headset"></i></span><span class="dt"><b>Help Desk</b><small>One inbox for every channel</small></span></a><a class="dropdown-item" href="flows.html"><span class="di"><i class="bi bi-bezier2"></i></span><span class="dt"><b>Flows</b><small>Automations that convert</small></span></a><a class="dropdown-item" href="integrations.html"><span class="di"><i class="bi bi-puzzle"></i></span><span class="dt"><b>Integrations</b><small>Connect your existing stack</small></span></a><a class="dropdown-item" href="features.html"><span class="di"><i class="bi bi-grid-1x2"></i></span><span class="dt"><b>All features</b><small>Everything in one platform</small></span></a><a class="dropdown-item" href="premium.html"><span class="di"><i class="bi bi-award"></i></span><span class="dt"><b>Premium plan</b><small>Done-for-you optimization</small></span></a></div></div><div class="dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Solutions</a><div class="dropdown-menu mega"><div class="mega-cols"><div><h6 class="dropdown-header">Teams</h6><a class="dropdown-item" href="solution-customer-service.html"><span class="di"><i class="bi bi-person-workspace"></i></span><span class="dt"><b>Customer Service</b><small>Resolve faster, with context</small></span></a><a class="dropdown-item" href="solution-marketing-sales.html"><span class="di"><i class="bi bi-bullseye"></i></span><span class="dt"><b>Marketing & Sales</b><small>Turn visitors into pipeline</small></span></a></div><div><h6 class="dropdown-header">Industries</h6><a class="dropdown-item" href="industry-ecommerce.html"><span class="di"><i class="bi bi-bag-check"></i></span><span class="dt"><b>Ecommerce</b><small>Recover carts, recommend</small></span></a><a class="dropdown-item" href="industry-services.html"><span class="di"><i class="bi bi-briefcase"></i></span><span class="dt"><b>Services</b><small>Qualify and book clients</small></span></a><a class="dropdown-item" href="industry-education.html"><span class="di"><i class="bi bi-mortarboard"></i></span><span class="dt"><b>Education</b><small>Support students 24/7</small></span></a><a class="dropdown-item" href="industry-finance.html"><span class="di"><i class="bi bi-bank2"></i></span><span class="dt"><b>Finance</b><small>Guardrails and audit logs</small></span></a><a class="dropdown-item" href="industry-saas.html"><span class="di"><i class="bi bi-window-stack"></i></span><span class="dt"><b>SaaS</b><small>Onboard and convert trials</small></span></a><a class="dropdown-item" href="industry-travel.html"><span class="di"><i class="bi bi-globe-europe-africa"></i></span><span class="dt"><b>Travel</b><small>Help travelers anywhere</small></span></a></div></div></div></div>
            <a href="{{ route('pricing') }}" class="nav-link">Pricing</a>
            <div class="dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Resources</a><div class="dropdown-menu mega"><div class="mega-cols"><div><a class="dropdown-item" href="customer-stories.html"><span class="di"><i class="bi bi-award"></i></span><span class="dt"><b>Customer Stories</b><small>Results from real teams</small></span></a><a class="dropdown-item" href="ebooks.html"><span class="di"><i class="bi bi-journal-bookmark"></i></span><span class="dt"><b>Ebooks</b><small>Guides to put to work</small></span></a><a class="dropdown-item" href="compare.html"><span class="di"><i class="bi bi-columns-gap"></i></span><span class="dt"><b>Compare</b><small>How Loop stacks up</small></span></a><a class="dropdown-item" href="help-center.html"><span class="di"><i class="bi bi-question-circle"></i></span><span class="dt"><b>Help Center</b><small>Step-by-step answers</small></span></a><a class="dropdown-item" href="blog.html"><span class="di"><i class="bi bi-pencil-square"></i></span><span class="dt"><b>Blog</b><small>Playbooks and news</small></span></a></div><div><a class="dropdown-item" href="partners.html"><span class="di"><i class="bi bi-people"></i></span><span class="dt"><b>Partner Program</b><small>Grow with Loop</small></span></a><a class="dropdown-item" href="roi-calculator.html"><span class="di"><i class="bi bi-calculator"></i></span><span class="dt"><b>ROI Calculator</b><small>Estimate your savings</small></span></a><a class="dropdown-item" href="watch-demo.html"><span class="di"><i class="bi bi-play-btn"></i></span><span class="dt"><b>Watch Demo</b><small>A short product tour</small></span></a><a class="dropdown-item" href="ai-playground.html"><span class="di"><i class="bi bi-stars"></i></span><span class="dt"><b>AI Playground</b><small>Try the agent yourself</small></span></a><a class="dropdown-item" href="resources.html"><span class="di"><i class="bi bi-collection"></i></span><span class="dt"><b>All resources</b><small>Browse everything</small></span></a></div></div></div></div>
            <a href="contact.html" class="nav-link">Contact</a>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="dropdown d-md-none"><a class="btn btn-outline-ink" href="#" data-bs-toggle="dropdown" aria-label="Menu"><i class="bi bi-list"></i></a><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="features.html"><span class="di"><i class="bi bi-grid-1x2"></i></span><span class="dt"><b>Product</b></span></a></li><li><a class="dropdown-item" href="solution-customer-service.html"><span class="di"><i class="bi bi-people"></i></span><span class="dt"><b>Solutions</b></span></a></li><li><a class="dropdown-item" href="{{ route('pricing') }}"><span class="di"><i class="bi bi-tag"></i></span><span class="dt"><b>Pricing</b></span></a></li><li><a class="dropdown-item" href="resources.html"><span class="di"><i class="bi bi-collection"></i></span><span class="dt"><b>Resources</b></span></a></li><li><a class="dropdown-item" href="contact.html"><span class="di"><i class="bi bi-envelope"></i></span><span class="dt"><b>Contact</b></span></a></li></ul></div>
            <a href="{{ route('login') }}" class="btn btn-outline-ink d-none d-sm-inline-block">Log in</a>
            <a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a>
        </div>
    </div>
</nav>

@yield('content')

<footer class="ft"><div class="container"><div class="row g-4"><div class="col-lg-4"><a href="{{ route('home') }}" class="brand" style="text-decoration:none">Loop</a><p style="margin-top:.75rem;max-width:32ch">Customer service that turns conversations into revenue. Join businesses that reply faster and sell more.</p><a href="{{ route('register') }}" class="btn btn-cobalt" style="display:inline-block;color:#fff;padding:.6rem 1.2rem">Start for free</a></div><div class="col-6 col-md-3 col-lg-2"><h6>Loop</h6><a href="about.html">About</a><a href="faq.html">FAQ</a><a href="contact.html">Contact</a><a href="partners.html">Partners</a><a href="careers.html">Careers</a><a href="security.html">Security</a><a href="trust.html">Trust</a><a href="reviews.html">Reviews</a><a href="newsroom.html">Newsroom</a></div><div class="col-6 col-md-3 col-lg-2"><h6>Product</h6><a href="{{ route('pricing') }}">Pricing</a><a href="ai-agent.html">AI Agent</a><a href="help-desk.html">Help Desk</a><a href="live-chat.html">Live Chat</a><a href="flows.html">Flows</a><a href="features.html">All features</a><a href="integrations.html">Integrations</a><a href="contact-sales.html">Contact sales</a></div><div class="col-6 col-md-3 col-lg-2"><h6>Resources</h6><a href="customer-stories.html">Customer Stories</a><a href="watch-demo.html">Watch Demo</a><a href="ai-playground.html">AI Playground</a><a href="roi-calculator.html">ROI Calculator</a><a href="ebooks.html">Ebooks</a><a href="blog.html">Blog</a><a href="compare.html">Compare</a></div><div class="col-6 col-md-3 col-lg-2"><h6>Support</h6><a href="help-center.html">Help Center</a><a href="developers.html">Developers</a><a href="status.html">Status</a><a href="updates.html">Product Updates</a><a href="roadmap.html">Roadmap</a><a href="mobile-sdk.html">Mobile SDK</a></div></div><div class="fine"><span>© 2026 Loop. All rights reserved.</span><span><a href="privacy-policy.html">Privacy Policy</a><a href="terms.html">Terms</a><a href="sitemap.html">Sitemap</a></span></div></div></footer>

@stack('page_styles_bottom')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('vendor/public-website/assets/glass-dark.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Reveal sections on scroll
    var targets = document.querySelectorAll('section, .price-card, .value-card, .feature-block, .feature-row');
    targets.forEach(function (el) { el.classList.add('reveal-el'); });

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    targets.forEach(function (el) { io.observe(el); });

    // Sticky nav shadow on scroll
    var nav = document.querySelector('.site-nav');
    if (nav) {
        window.addEventListener('scroll', function () {
            nav.classList.toggle('scrolled', window.scrollY > 8);
        });
    }
});
</script>
@stack('scripts')
</body>
</html>
