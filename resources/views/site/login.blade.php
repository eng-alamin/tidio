@extends('layouts.auth')

@section('title', 'Log in — Loop')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="eyebrow">Welcome back</div>
        <h1>Log in to Loop</h1>
        <p class="sub">Pick up where your team left off.</p>

        <form method="POST" action="{{ route('login.attempt') }}">
    @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Work email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="you@company.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label" for="password">Password</label>
                </div>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="••••••••">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
<p style="text-align:right;margin:.4rem 0 0"><a href="#" style="color:#9fc0ff;font-size:.88rem">Forgot password?</a></p>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">Keep me signed in</label>
            </div>
            <button type="submit" class="btn btn-cobalt">Log in</button>
        </form>

        <div class="divider">or</div>

        <button type="button" class="btn btn-social">
            <i class="bi bi-google"></i> Continue with Google
        </button>

        <div class="auth-footer">
            Don't have an account? <a href="{{ route('register') }}">Start free trial</a>
        </div>
    </div>
</div>

@endsection
