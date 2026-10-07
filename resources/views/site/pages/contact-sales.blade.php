@extends('layouts.site')
@section('title', 'Contact Sales — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Contact sales</div><h1>Talk to our team</h1><p>Tell us what you need and we will design a plan around it.</p></div></section>
<section class="sec"><div class="container"><div class="glass" style="max-width:640px;margin:0 auto"><form method="POST" action="{{ route('site.contact-sales.store') }}" novalidate>
@csrf
<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
@if (session('lead_status'))<div class="alert alert-success mb-3" role="status">{{ session('lead_status') }}</div>@endif
<div class="row g-3">
<div class="col-md-6"><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Full name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Work email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><input type="text" name="company" class="form-control @error('company') is-invalid @enderror" placeholder="Company" value="{{ old('company') }}" >@error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><select name="team_size" class="form-select @error('team_size') is-invalid @enderror"><option value="">Team size</option>@foreach (['1–10','11–50','51–200','200+'] as $size)<option value="{{ $size }}" @selected(old('team_size') === $size)>{{ $size }}</option>@endforeach</select>@error('team_size')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><textarea name="message" rows="4" class="form-control @error('message') is-invalid @enderror" placeholder="What are you looking to solve?">{{ old('message') }}</textarea>@error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><button type="submit" class="btn btn-cobalt w-100">Request a call</button></div>
</div>
</form></div></div></section>
@endsection
