@extends('layouts.site')
@section('title', 'About — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-331d0c.css') }}">
@endpush

@section('content')
<!-- Header -->
<section class="about-header">
    <div class="container">
        <div class="eyebrow">About Loop</div>
        <h1>We think support should feel like a conversation, not a queue</h1>
        <p>We build tools that help small teams reply like a much bigger one — fast, personal, and without the busywork.</p>
    </div>
</section>

<!-- Mission -->
<section class="mission-section">
    <div class="container">
        <p class="big">Every customer message is a chance to help someone — we just make sure it never gets lost in the pile.</p>
    </div>
</section>

<!-- Stats -->
<section class="about-stats">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <div class="stat-num">2019</div>
                <div class="stat-label">Founded</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-num">2,400+</div>
                <div class="stat-label">Teams onboard</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-num">40</div>
                <div class="stat-label">Countries served</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-num">28</div>
                <div class="stat-label">People on the team</div>
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="values-section">
    <div class="container">
        <h2>What we build around</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="value-card">
                    <div class="icon"><i class="bi bi-lightning-charge"></i></div>
                    <h3>Speed over ceremony</h3>
                    <p>Fewer clicks, fewer settings screens. If a feature takes ten minutes to explain, we simplify it.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="icon"><i class="bi bi-people"></i></div>
                    <h3>Built with real teams</h3>
                    <p>Every feature ships after weeks with actual support teams, not just a roadmap meeting.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="icon"><i class="bi bi-shield-check"></i></div>
                    <h3>Trust by default</h3>
                    <p>Your customers' data is treated like it's ours — encrypted, audited, and never sold.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team -->
<section class="team-section">
    <div class="container">
        <h2>The people behind Loop</h2>
        <p class="sub">A small, distributed team across four time zones, all working the support queue at least once a quarter.</p>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3 team-member">
                <div class="team-avatar">R</div>
                <h4>Rafi Ahmed</h4>
                <p>Co-founder & CEO</p>
            </div>
            <div class="col-6 col-md-3 team-member">
                <div class="team-avatar">N</div>
                <h4>Nadia Islam</h4>
                <p>Co-founder & Product</p>
            </div>
            <div class="col-6 col-md-3 team-member">
                <div class="team-avatar">T</div>
                <h4>Tanvir Rahman</h4>
                <p>Engineering Lead</p>
            </div>
            <div class="col-6 col-md-3 team-member">
                <div class="team-avatar">S</div>
                <h4>Sara Chowdhury</h4>
                <p>Head of Support</p>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="final-cta">
    <div class="container">
        <h2>Come see what Loop can do for your team</h2>
        <a href="{{ route('register') }}" class="btn btn-cobalt">Start free trial</a>
    </div>
</section>
@endsection
