@extends('layouts.site')

@php
    $seoTitle = $post->seo_meta['title'] ?? $post->title;
    $seoDescription = $post->seo_meta['description'] ?? $post->excerpt;
    $toc = $post->toc;
@endphp

@section('title', $seoTitle.' — Loop Blog')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
<meta name="description" content="{{ $seoDescription }}">
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $post->url }}">
<link rel="canonical" href="{{ $post->url }}">
@endpush

@section('content')
<section class="art-head"><div class="container">
    <div class="crumbs">
        <a href="{{ route('home') }}">Home</a><i class="bi bi-chevron-right"></i><a href="{{ route('site.blog') }}">Blog</a>
        @if ($post->category)<i class="bi bi-chevron-right"></i><a href="{{ route('site.blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>@endif
    </div>
    @if ($post->category)<span class="tag">{{ $post->category->name }}</span>@endif
    <h1>{{ $post->title }}</h1>
    @if ($post->excerpt)<p style="color:var(--ink-soft);max-width:60ch;font-size:1.1rem">{{ $post->excerpt }}</p>@endif
    <div class="byline">
        <span class="avatar">{{ $post->author_initials }}</span>
        <span>Written by <b style="color:#fff">{{ $post->author_name }}</b></span>
        <span>Updated {{ $post->published_at->format('M j, Y') }}</span>
        <span>{{ $post->read_minutes }} min read</span>
    </div>
</div></section>

<section class="sec"><div class="container"><div class="row g-5">
    @if ($toc)
        <aside class="col-lg-3 d-none d-lg-block"><div class="toc">
            <h6>On this page</h6>
            @foreach ($toc as $entry)<a href="#{{ $entry['id'] }}">{{ $entry['title'] }}</a>@endforeach
            <div class="share" style="margin-top:1.5rem">
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($post->url) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" aria-label="Share on X"><i class="bi bi-twitter-x"></i></a>
                <a href="#" data-copy-link aria-label="Copy link"><i class="bi bi-link-45deg"></i></a>
            </div>
        </div></aside>
    @endif

    <article class="{{ $toc ? 'col-lg-9' : 'col-lg-8 mx-auto' }}"><div class="prose">
        {!! $post->body_html !!}

        <div class="glass author-box" style="margin-top:3rem">
            <span class="avatar" style="width:56px;height:56px">{{ $post->author_initials }}</span>
            <div>
                <b style="color:#fff">{{ $post->author_name }}</b>
                <p style="margin:0;font-size:.95rem">Writes about customer service and conversational AI at Loop.</p>
            </div>
        </div>
        <div class="glass" style="margin-top:2rem;text-align:center">
            <h3 style="font-family:var(--font-display)">Automate up to 67% of common questions</h3>
            <p style="margin:.5rem 0 1.25rem">Try the Loop AI agent free, no credit card needed.</p>
            <a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a>
        </div>
    </div></article>
</div></div></section>

@if ($related->isNotEmpty())
<section class="sec"><div class="container">
    <h2 class="h2s">Keep reading</h2><div style="height:1rem"></div>
    <div class="tile-grid">
        @foreach ($related as $item)
            <a class="tile" href="{{ $item->url }}">
                @if ($item->category)<span class="tag">{{ $item->category->name }}</span>@endif
                <h3>{{ $item->title }}</h3><p>{{ $item->excerpt }}</p>
            </a>
        @endforeach
    </div>
</div></section>
@endif

<section class="final-cta"><div class="container">
    <h2>What will your next 100 conversations earn?</h2>
    <a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a>
    <p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p>
</div></section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var links = document.querySelectorAll('.toc a[href^="#"]');
    if (links.length && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (x) {
                if (x.isIntersecting) {
                    links.forEach(function (a) { a.classList.toggle('on', a.getAttribute('href') === '#' + x.target.id); });
                }
            });
        }, { rootMargin: '-20% 0px -70% 0px' });
        document.querySelectorAll('.prose h2[id]').forEach(function (h) { io.observe(h); });
    }
    document.querySelectorAll('[data-copy-link]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            if (navigator.clipboard) { navigator.clipboard.writeText(location.href.split('#')[0]); }
        });
    });
});
</script>
@endpush
