@extends('layouts.site')

@section('title', ($category ? $category->name.' — Blog' : 'Blog').' — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container">
    <div class="eyebrow">{{ $category ? 'Blog category' : 'Blog' }}</div>
    <h1>{{ $category ? $category->name : 'Notes on better customer conversations' }}</h1>
    <p>{{ $category ? 'Articles about '.\Illuminate\Support\Str::lower($category->name).'.' : 'Playbooks, product news and lessons from the teams who talk to customers all day.' }}</p>

    <form method="GET" action="{{ $category ? route('site.blog.category', $category->slug) : route('site.blog') }}" class="search-box" role="search">
        <i class="bi bi-search"></i>
        <input name="q" type="search" value="{{ $q }}" class="form-control"
               placeholder="{{ $category ? 'Search in '.$category->name : 'Search articles' }}" aria-label="Search articles">
    </form>

    <div class="chips">
        <a class="chip {{ $category ? '' : 'on' }}" href="{{ route('site.blog') }}">All</a>
        @foreach ($categories as $cat)
            <a class="chip {{ $category?->id === $cat->id ? 'on' : '' }}" href="{{ route('site.blog.category', $cat->slug) }}">{{ $cat->name }}</a>
        @endforeach
    </div>
</div></section>

<section class="sec"><div class="container">
    @if ($featured)
        <a class="tile glass" href="{{ $featured->url }}" style="display:grid;gap:1.5rem;grid-template-columns:1fr 1.3fr;align-items:center;margin-bottom:2rem">
            <div class="post-thumb glass" style="height:220px;margin:0"><i class="bi {{ $featured->icon }}" style="font-size:3.5rem"></i></div>
            <div>
                <span class="tag">Featured{{ $featured->category ? ' · '.$featured->category->name : '' }}</span>
                <h2 class="h2s">{{ $featured->title }}</h2>
                <p>{{ $featured->excerpt }}</p>
                <small style="color:var(--ink-soft)">{{ $featured->published_at->format('M j, Y') }} · {{ $featured->read_minutes }} min read</small>
            </div>
        </a>
    @endif

    @if ($items->isNotEmpty())
        <div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
            @foreach ($items as $post)
                @include('site.blog._tile', ['post' => $post])
            @endforeach
        </div>
    @elseif (! $featured)
        <p style="text-align:center;color:var(--ink-soft)">
            {{ $q !== '' ? 'No articles match your search.' : 'No articles here yet.' }}
            @if ($q !== '' || $category)<a href="{{ route('site.blog') }}">See all articles</a>@endif
        </p>
    @endif

    @if ($posts->hasPages())
        <div class="mt-4 d-flex justify-content-center">{{ $posts->links('pagination::bootstrap-5') }}</div>
    @endif

    <div class="glass" style="max-width:640px;margin:3rem auto 0;text-align:center">
        <h3 style="font-family:var(--font-display)">Get new posts in your inbox</h3>
        <p>One short email a month. No spam.</p>
        <a href="#newsletter" class="btn btn-cobalt">Subscribe</a>
    </div>
</div></section>

<section class="final-cta"><div class="container">
    <h2>What will your next 100 conversations earn?</h2>
    <a href="{{ route('register') }}" class="btn btn-cobalt">Start for free</a>
    <p style="margin-top:1rem;color:var(--ink-soft);font-size:.9rem">No credit card required</p>
</div></section>
@endsection
