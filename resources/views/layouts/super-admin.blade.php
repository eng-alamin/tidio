<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Loop — {{ $title ?? 'Super Admin' }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>try{document.documentElement.setAttribute('data-theme', localStorage.getItem('loop-theme') || 'dark');}catch(e){}</script>
<link rel="stylesheet" href="{{ asset('vendor/super-admin/style.css') }}">
<style>
.pagination{--bs-pagination-bg:transparent;--bs-pagination-color:var(--text);--bs-pagination-border-color:var(--border,rgba(255,255,255,.12));--bs-pagination-hover-bg:var(--cobalt-soft);--bs-pagination-hover-color:var(--text);--bs-pagination-active-bg:var(--cobalt);--bs-pagination-active-border-color:var(--cobalt);--bs-pagination-disabled-bg:transparent;--bs-pagination-disabled-color:var(--text-soft);--bs-pagination-disabled-border-color:var(--border,rgba(255,255,255,.12));--bs-pagination-focus-bg:var(--cobalt-soft);--bs-pagination-focus-color:var(--text);margin:0;flex-wrap:wrap;}
</style>
@livewireStyles
</head>
<body>
@php
    $admin = auth('super_admin')->user();
    $initials = collect(explode(' ', trim($admin->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $roleLabel = ['super_admin' => 'Super Admin', 'support_staff' => 'Support Staff', 'billing_admin' => 'Billing Admin'][$admin->role->value] ?? 'Staff';
@endphp
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="bg-orbs" aria-hidden="true"><div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div></div>
<div class="sidebar-scrim" id="sidebarScrim"></div>
<div class="app-shell">
  <nav class="sidebar" id="sidebar" aria-label="Main navigation">
    <a href="{{ route('admin.dashboard') }}" class="brand"><span class="dot" aria-hidden="true"></span> Loop</a>
    <div class="nav-scroll">
      <div class="nav-section-label">Overview</div>
      <a class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i> Dashboard</a>
      <a class="nav-item" href="#" data-toast="Analytics is coming soon."><i class="bi bi-bar-chart-line-fill" aria-hidden="true"></i> Analytics</a>
      <div class="nav-section-label">Platform</div>
      <a class="nav-item {{ request()->routeIs('admin.tenants') ? 'active' : '' }}" href="{{ route('admin.tenants') }}" @if(request()->routeIs('admin.tenants')) aria-current="page" @endif><i class="bi bi-buildings-fill" aria-hidden="true"></i> Tenants</a>
      <a class="nav-item" href="#" data-toast="Onboarding is coming soon."><i class="bi bi-list-check" aria-hidden="true"></i> Onboarding</a>
      <a class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}" @if(request()->routeIs('admin.users')) aria-current="page" @endif><i class="bi bi-people-fill" aria-hidden="true"></i> Users</a>
      <a class="nav-item {{ request()->routeIs('admin.subscriptions') ? 'active' : '' }}" href="{{ route('admin.subscriptions') }}" @if(request()->routeIs('admin.subscriptions')) aria-current="page" @endif><i class="bi bi-credit-card-2-front-fill" aria-hidden="true"></i> Subscriptions</a>
      <a class="nav-item {{ request()->routeIs('admin.billing') ? 'active' : '' }}" href="{{ route('admin.billing') }}" @if(request()->routeIs('admin.billing')) aria-current="page" @endif><i class="bi bi-receipt" aria-hidden="true"></i> Billing &amp; Invoices</a>
      <a class="nav-item {{ request()->routeIs('admin.feature-flags') ? 'active' : '' }}" href="{{ route('admin.feature-flags') }}" @if(request()->routeIs('admin.feature-flags')) aria-current="page" @endif><i class="bi bi-toggles" aria-hidden="true"></i> Feature Flags</a>
      <a class="nav-item {{ request()->routeIs('admin.coupons') ? 'active' : '' }}" href="{{ route('admin.coupons') }}" @if(request()->routeIs('admin.coupons')) aria-current="page" @endif><i class="bi bi-ticket-perforated-fill" aria-hidden="true"></i> Coupons</a>
      <a class="nav-item {{ request()->routeIs('admin.api-access') ? 'active' : '' }}" href="{{ route('admin.api-access') }}" @if(request()->routeIs('admin.api-access')) aria-current="page" @endif><i class="bi bi-key-fill" aria-hidden="true"></i> API Keys &amp; Webhooks</a>
      <a class="nav-item {{ request()->routeIs('admin.integrations') ? 'active' : '' }}" href="{{ route('admin.integrations') }}" @if(request()->routeIs('admin.integrations')) aria-current="page" @endif><i class="bi bi-plug-fill" aria-hidden="true"></i> Integrations</a>
      @if ($admin->role === \App\Enums\SuperAdminRole::SuperAdmin)
      <div class="nav-section-label">Access</div>
      <a class="nav-item {{ request()->routeIs('admin.roles') ? 'active' : '' }}" href="{{ route('admin.roles') }}" @if(request()->routeIs('admin.roles')) aria-current="page" @endif><i class="bi bi-shield-lock-fill" aria-hidden="true"></i> Roles &amp; Permissions</a>
      @endif
      <div class="nav-section-label">Support</div>
      <a class="nav-item {{ request()->routeIs('admin.support-inbox') ? 'active' : '' }}" href="{{ route('admin.support-inbox') }}" @if(request()->routeIs('admin.support-inbox')) aria-current="page" @endif><i class="bi bi-headset" aria-hidden="true"></i> Support Inbox</a>
      <div class="nav-section-label">System</div>
      <a class="nav-item {{ request()->routeIs('admin.activity-log') ? 'active' : '' }}" href="{{ route('admin.activity-log') }}" @if(request()->routeIs('admin.activity-log')) aria-current="page" @endif><i class="bi bi-terminal-fill" aria-hidden="true"></i> Activity &amp; Audit Log</a>
      <a class="nav-item {{ request()->routeIs('admin.notifications') ? 'active' : '' }}" href="{{ route('admin.notifications') }}" @if(request()->routeIs('admin.notifications')) aria-current="page" @endif><i class="bi bi-bell-fill" aria-hidden="true"></i> Notifications</a>
      <a class="nav-item" href="#" data-toast="Settings is coming soon."><i class="bi bi-gear-fill" aria-hidden="true"></i> Settings</a>
      <div class="sidebar-foot">
        <form method="POST" action="{{ route('admin.logout') }}">
          @csrf
          <button type="submit" class="nav-item" style="width:100%; background:none; border:0; text-align:left;"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log out</button>
        </form>
      </div>
    </div>
  </nav>

  <div class="main">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:0;">
        <button class="icon-btn hamburger-btn" id="hamburgerBtn" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
      </div>
      <div class="topbar-actions">
        <button class="icon-btn" id="themeToggleBtn" type="button" aria-label="Toggle dark or light theme"><i class="bi bi-moon-stars-fill" id="themeIcon" aria-hidden="true"></i></button>

        <div class="dropdown-wrap">
          <button class="admin-chip" id="avatarBtn" type="button" aria-label="Account menu">
            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
            <span style="line-height:1.1; text-align:left;">
              <span style="font-size:.82rem; font-weight:600; display:block;">{{ $admin->name }}</span>
              <span style="font-size:.7rem; color:var(--text-soft); display:block;">{{ $roleLabel }}</span>
            </span>
            <i class="bi bi-chevron-down" style="font-size:.7rem; color:var(--text-soft);" aria-hidden="true"></i>
          </button>
          <div class="dropdown-panel wide" id="avatarPanel" role="menu">
            <div class="dd-header">{{ $admin->email }}</div>
            <form method="POST" action="{{ route('admin.logout') }}">
              @csrf
              <button type="submit" class="dd-item" role="menuitem"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="t">Log out</span></button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main class="content" id="main-content">
      {{ $slot }}
    </main>

    <nav class="mobile-tabbar" aria-label="Primary">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>Home</a>
      <a href="{{ route('admin.tenants') }}" class="{{ request()->routeIs('admin.tenants') ? 'active' : '' }}"><i class="bi bi-buildings-fill" aria-hidden="true"></i>Tenants</a>
      <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}"><i class="bi bi-people-fill" aria-hidden="true"></i>Users</a>
    </nav>
  </div>
</div>

<div class="toast-wrap" id="toastWrap" aria-live="polite" wire:ignore></div>

@livewireScripts
<script src="{{ asset('vendor/super-admin/shell.js') }}"></script>
</body>
</html>
