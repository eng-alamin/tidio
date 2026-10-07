@extends('layouts.site')
@section('title', 'ROI Calculator — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">ROI calculator</div><h1>What could automation save you?</h1><p>Adjust the numbers to estimate monthly savings.</p></div></section>
<section class="sec"><div class="container"><div class="glass" style="max-width:640px;margin:0 auto"><label>Conversations per month</label><input id="a" type="number" class="form-control mb-3" value="3000"><label>Minutes per conversation</label><input id="b" type="number" class="form-control mb-3" value="6"><label>Agent cost per hour ($)</label><input id="c" type="number" class="form-control mb-3" value="18"><label>Share handled by AI: <b id="pv">60</b>%</label><input id="d" type="range" class="form-range mb-4" min="10" max="90" value="60"><div class="stat3" style="margin:0"><div><b id="o1">$0</b><span>saved per month</span></div><div><b id="o2">0</b><span>hours returned</span></div></div></div></div></section><script>(function(){function g(i){return parseFloat(document.getElementById(i).value)||0}function u(){var h=g("a")*g("b")/60*g("d")/100;document.getElementById("pv").textContent=g("d");document.getElementById("o2").textContent=Math.round(h).toLocaleString();document.getElementById("o1").textContent="$"+Math.round(h*g("c")).toLocaleString()}["a","b","c","d"].forEach(function(i){document.getElementById(i).oninput=u});u()})()</script>
@endsection
