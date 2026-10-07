@extends('layouts.site')
@section('title', 'Product Updates — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Product updates</div><h1>What's new in Loop</h1><p>A running log of improvements.</p></div></section>
<section class="sec"><div class="container" style="max-width:760px"><div class="glass mb-3"><span class="tag">New</span><h3 style="font-size:1.1rem">Smarter handoffs</h3><p style="color:var(--ink-soft);margin:0">The agent now passes full context to the right team.</p><small style="color:var(--ink-soft)">Sep 9, 2026</small></div><div class="glass mb-3"><span class="tag">Improved</span><h3 style="font-size:1.1rem">Faster inbox search</h3><p style="color:var(--ink-soft);margin:0">Find any conversation in under a second.</p><small style="color:var(--ink-soft)">Aug 26, 2026</small></div><div class="glass mb-3"><span class="tag">New</span><h3 style="font-size:1.1rem">Cart recovery templates</h3><p style="color:var(--ink-soft);margin:0">Ten new ready-made flows for stores.</p><small style="color:var(--ink-soft)">Aug 4, 2026</small></div></div></section>
@endsection
