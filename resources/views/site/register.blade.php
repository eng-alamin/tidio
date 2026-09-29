@extends('layouts.auth')

@section('title', 'Create your account — Loop')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="eyebrow">Start for free</div>
        <h1>Create your account</h1>
        <p class="sub">Free forever, no credit card required.</p>

        <form method="POST" action="{{ route('register.attempt') }}">
    @csrf
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="fname">First name</label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="fname" name="first_name" value="{{ old('first_name') }}" placeholder="Jane">
                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="lname">Last name</label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="lname" name="last_name" value="{{ old('last_name') }}" placeholder="Doe">
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="remail">Work email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="remail" name="email" value="{{ old('email') }}" placeholder="you@company.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="company">Company name</label>
                <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company" name="company_name" value="{{ old('company_name') }}" placeholder="Acme Inc.">
                @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="rpassword">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="rpassword" name="password" placeholder="At least 8 characters">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms">
                <label class="form-check-label" for="terms">I agree to the Terms and Privacy Policy</label>
            </div>
            <button type="submit" class="btn btn-cobalt">Create account</button>
        </form>

        <div class="divider">or</div>

        <button type="button" class="btn btn-social">
            <i class="bi bi-google"></i> Continue with Google
        </button>

        <div class="auth-footer">
            Already have an account? <a href="{{ route('login') }}">Log in</a>
        </div>
    </div>
</div>
@endsection
