@extends('layouts.site')
@section('title', 'Contact — Loop')

@push('styles')
<style>

    :root {
        --ink: #14213D;
        --ink-soft: #4A5578;
        --paper: #FBF9F4;
        --cobalt: #2B5FE2;
        --cobalt-dark: #1E46B3;
        --mist: #E7ECF5;
        --line: #DCE2EF;
        --font-display: 'Fraunces', Georgia, serif;
        --font-body: 'Inter', system-ui, sans-serif;
    }
    body { background: var(--paper); color: var(--ink); font-family: var(--font-body); }

    .site-nav { padding: 1.25rem 0; border-bottom: 1px solid var(--line); }
    .site-nav .brand { font-family: var(--font-display); font-weight: 600; font-size: 1.3rem; color: var(--ink); text-decoration: none; }
    .site-nav a.nav-link { color: var(--ink-soft); font-weight: 500; font-size: 0.95rem; }
    .site-nav a.nav-link:hover { color: var(--ink); }
    .btn-cobalt { background: var(--cobalt); color: #fff; font-weight: 600; padding: 0.7rem 1.5rem; border-radius: 8px; border: none; }
    .btn-cobalt:hover { background: var(--cobalt-dark); color: #fff; }
    .btn-outline-ink { background: transparent; color: var(--ink); font-weight: 600; padding: 0.7rem 1.5rem; border-radius: 8px; border: 1.5px solid var(--line); }
    .btn-outline-ink:hover { border-color: var(--ink); }

    .contact-header { padding: 4.5rem 0 2.5rem; text-align: center; }
    .contact-header .eyebrow { font-weight: 600; font-size: 0.95rem; color: var(--cobalt); margin-bottom: 1rem; }
    .contact-header h1 {
        font-family: var(--font-display); font-weight: 500;
        font-size: clamp(2rem, 3.6vw, 2.7rem); max-width: 20ch; margin: 0 auto 1.1rem;
    }
    .contact-header p { color: var(--ink-soft); font-size: 1.05rem; max-width: 46ch; margin: 0 auto; }

    .contact-wrap { padding-bottom: 5rem; }

    .contact-form-card {
        background: #fff; border: 1px solid var(--line); border-radius: 16px;
        padding: 2.25rem; box-shadow: 0 20px 44px -30px rgba(20,33,61,0.25);
    }
    .form-label { font-weight: 600; font-size: 0.88rem; }
    .form-control, .form-select {
        border: 1.5px solid var(--line); border-radius: 8px; padding: 0.65rem 0.9rem; font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus { border-color: var(--cobalt); box-shadow: 0 0 0 3px rgba(43,95,226,0.12); }

    .contact-info-list { display: flex; flex-direction: column; gap: 1.5rem; }
    .contact-info-item { display: flex; gap: 1rem; }
    .contact-info-item .icon {
        width: 44px; height: 44px; background: var(--mist); border-radius: 10px;
        display: flex; align-items: center; justify-content: center; color: var(--cobalt);
        flex-shrink: 0; font-size: 1.15rem;
    }
    .contact-info-item h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.15rem; }
    .contact-info-item p { color: var(--ink-soft); font-size: 0.9rem; margin: 0; }
    .contact-info-item a { color: var(--cobalt); font-weight: 600; text-decoration: none; }


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
<section class="contact-header">
    <div class="container">
        <div class="eyebrow">Contact</div>
        <h1>Talk to a real person on our team</h1>
        <p>Questions about pricing, a demo, or migrating from another tool — send it over and we'll reply within a day.</p>
    </div>
</section>

<!-- Form + info -->
<section class="contact-wrap">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="contact-form-card">
                    <form method="POST" action="{{ route('site.contact.store') }}" novalidate>
@csrf
<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
@if (session('lead_status'))<div class="alert alert-success mb-3" role="status">{{ session('lead_status') }}</div>@endif
<div class="row g-3 mb-3">
<div class="col-md-6"><label class="form-label" for="name">Full name</label><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Jane Doe" value="{{ old('name') }}" id="name" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label class="form-label" for="cemail">Work email</label><input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="you@company.com" value="{{ old('email') }}" id="cemail" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
</div>
<div class="mb-3"><label class="form-label" for="topic">What's this about?</label><select name="topic" id="topic" class="form-select @error('topic') is-invalid @enderror">@foreach (['General question','Sales & pricing','Technical support','Partnership'] as $topic)<option value="{{ $topic }}" @selected(old('topic') === $topic)>{{ $topic }}</option>@endforeach</select>@error('topic')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label" for="message">Message</label><textarea name="message" id="message" rows="5" class="form-control @error('message') is-invalid @enderror" placeholder="Tell us a bit about what you need..." required>{{ old('message') }}</textarea>@error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<button type="submit" class="btn btn-cobalt">Send message</button>
</form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <div class="icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <h4>Email us</h4>
                            <p><a href="mailto:hello@@loop.app">hello@@loop.app</a></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="icon"><i class="bi bi-chat-dots"></i></div>
                        <div>
                            <h4>Live chat</h4>
                            <p>Chat with our team, weekdays 9am–6pm</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <h4>Office</h4>
                            <p>Dhaka, Bangladesh</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="icon"><i class="bi bi-question-circle"></i></div>
                        <div>
                            <h4>Looking for support?</h4>
                            <p>Existing customers can log in and reach us directly from the app.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
