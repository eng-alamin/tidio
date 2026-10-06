<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f3f5fb">
<title>{{ $title ?? 'Dashboard' }} · Loop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('theme')||(matchMedia('(prefers-color-scheme:light)').matches?'glass':'light')}catch(e){}</script>
<link href="{{ asset('vendor/app-panel/app.css') }}" rel="stylesheet">
@livewireStyles
@stack('styles')
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
@include('partials.impersonation-banner')

<div class="banner" role="status">
    <i class="bi bi-plug" aria-hidden="true"></i>
    <span>Install the chat widget to enable Flows, Lyro AI Agent and live chat. <a href="{{ route('app.settings') }}"><u>Install chat widget</u></a></span>
    <button class="ib" data-dismiss aria-label="Dismiss notice"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
</div>

<div class="shell">
    <nav class="rail" aria-label="Main">
        <a class="logo" href="{{ route('app.dashboard') }}" aria-label="Loop home">L</a>
        <a href="{{ route('app.dashboard') }}" class="{{ request()->routeIs('app.dashboard') ? 'on' : '' }}" @if(request()->routeIs('app.dashboard')) aria-current="page" @endif>
            <i class="bi bi-grid" aria-hidden="true"></i><span>Home</span>
        </a>
        <a href="{{ route('app.inbox') }}" class="{{ request()->routeIs('app.inbox') ? 'on' : '' }}" @if(request()->routeIs('app.inbox')) aria-current="page" @endif>
            <i class="bi bi-inbox" aria-hidden="true"></i><span>Inbox</span>
        </a>
        <a href="{{ route('app.lyro') }}" class="{{ request()->routeIs('app.lyro') ? 'on' : '' }}" @if(request()->routeIs('app.lyro')) aria-current="page" @endif>
            <i class="bi bi-robot" aria-hidden="true"></i><span>Lyro</span>
        </a>
        <a href="{{ route('app.flows') }}" class="{{ request()->routeIs('app.flows') ? 'on' : '' }}" @if(request()->routeIs('app.flows')) aria-current="page" @endif>
            <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Flows</span>
        </a>
        <a href="{{ route('app.customers') }}" class="{{ request()->routeIs('app.customers') ? 'on' : '' }}" @if(request()->routeIs('app.customers')) aria-current="page" @endif>
            <i class="bi bi-people" aria-hidden="true"></i><span>Customers</span>
        </a>
        <a href="{{ route('app.analytics') }}" class="{{ request()->routeIs('app.analytics') ? 'on' : '' }}">
            <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Analytics</span>
        </a>
        <a href="{{ route('app.settings') }}" class="{{ request()->routeIs('app.settings') ? 'on' : '' }}" @if(request()->routeIs('app.settings')) aria-current="page" @endif>
            <i class="bi bi-gear" aria-hidden="true"></i><span>Settings</span>
        </a>
        <span class="sp"></span>
        <a class="acct" href="{{ route('app.settings') }}" aria-label="Account"><i class="bi bi-person-circle" aria-hidden="true"></i></a>
    </nav>

    <div class="main">
        <header class="top">
            <h1>{{ $title ?? 'Dashboard' }}</h1>
            <button class="ib" data-toast="Help center opens here." aria-label="Help"><i class="bi bi-question-circle" aria-hidden="true"></i></button>
            <livewire:app.notification-bell />
            <button class="ib" id="themeBtn" aria-label="Toggle dark mode"><i class="bi bi-moon-stars" aria-hidden="true"></i></button>
            <div class="trial"><b>7</b><span>days left in your trial</span></div>
            <button class="btn up">Upgrade</button>
            <a class="ib me" href="{{ route('app.settings') }}" aria-label="Account"><i class="bi bi-person-circle" aria-hidden="true"></i></a>
        </header>

        <div id="main" role="main">
            {{ $slot }}
        </div>
    </div>
</div>

<script src="{{ asset('vendor/app-panel/app.js') }}"></script>
<script src="{{ asset('vendor/app-panel/ui.js') }}"></script>
@livewireScripts
@stack('scripts')
</body>
</html>