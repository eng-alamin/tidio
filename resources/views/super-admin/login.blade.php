<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Loop — Super Admin sign in</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>try{document.documentElement.setAttribute('data-theme', localStorage.getItem('loop-theme') || 'dark');}catch(e){}</script>
<link rel="stylesheet" href="{{ asset('vendor/super-admin/style.css') }}">
</head>
<body>
<div class="bg-orbs" aria-hidden="true"><div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div></div>

<main style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;">
  <div class="glass panel" style="width:100%; max-width:420px; padding:32px;">
    <div class="sidebar" style="position:static; width:auto; height:auto; background:none; border:0; padding:0; margin-bottom:20px;">
      <span class="brand" style="padding:0;"><span class="dot" aria-hidden="true"></span> <span>Loop</span></span>
    </div>
    <h2 style="font-family:'Fraunces',serif; font-size:1.5rem; margin-bottom:4px;">Super Admin</h2>
    <p style="color:var(--text-soft); font-size:.88rem; margin-bottom:24px;">Sign in with your platform staff account.</p>

    <form method="POST" action="{{ route('admin.login.attempt') }}" novalidate>
      @csrf

      <div class="form-field">
        <label class="form-label" for="email">Email</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        @error('email')
          <div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>
        @enderror
      </div>

      <div class="form-field">
        <label class="form-label" for="password">Password</label>
        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password')
          <div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>
        @enderror
      </div>

      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
        <label class="form-check-label" for="remember" style="font-size:.85rem;">Keep me signed in</label>
      </div>

      <button type="submit" class="btn btn-cobalt" style="width:100%;">Sign in</button>
    </form>
  </div>
</main>
</body>
</html>
