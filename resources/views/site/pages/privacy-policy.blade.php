@extends('layouts.site')
@section('title', 'Privacy Policy — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Legal</div><h1>Privacy Policy</h1><p>Last updated September 2026.</p></div></section>
<section class="sec"><div class="container"><div class="legal glass"><p><small>Template text for demonstration. Replace with reviewed legal content before launch.</small></p><h2>What we collect</h2><p>Account details, conversation content and usage data needed to run the service.</p><h2>How we use it</h2><p>To provide, secure and improve Loop, and to support your customers.</p><h2>Your rights</h2><p>You can access, export or delete your data at any time.</p><h2>Contact</h2><p>Write to privacy@@loop.app with any question.</p></div></div></section>
@endsection
