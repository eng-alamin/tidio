@extends('layouts.site')
@section('title', 'Roadmap — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Roadmap</div><h1>Where we are heading</h1><p>Priorities shaped by what customers ask for.</p></div></section>
<section class="sec"><div class="container"><div class="row g-4"><div class="col-md-4"><div class="glass h-100"><h3 class="h2s" style="font-size:1.3rem">Now</h3><ul style="padding-left:1.1rem;color:var(--ink-soft);line-height:1.9;margin:0"><li>Voice replies</li><li>Custom reports</li></ul></div></div><div class="col-md-4"><div class="glass h-100"><h3 class="h2s" style="font-size:1.3rem">Next</h3><ul style="padding-left:1.1rem;color:var(--ink-soft);line-height:1.9;margin:0"><li>WhatsApp templates</li><li>Team scheduling</li></ul></div></div><div class="col-md-4"><div class="glass h-100"><h3 class="h2s" style="font-size:1.3rem">Later</h3><ul style="padding-left:1.1rem;color:var(--ink-soft);line-height:1.9;margin:0"><li>Video chat</li><li>Marketplace for flows</li></ul></div></div></div></div></section>
@endsection
