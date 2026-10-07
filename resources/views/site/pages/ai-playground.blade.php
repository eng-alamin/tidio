@extends('layouts.site')
@section('title', 'AI Playground — Loop')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/public-website/assets/pages-base.css') }}">
@endpush

@section('content')
<section class="about-header"><div class="container"><div class="eyebrow">Playground</div><h1>Try the agent yourself</h1><p>Ask a question and see how an agent trained on sample store content responds.</p></div></section>
<section class="sec"><div class="container"><div class="glass chat-demo"><div class="log" id="log"><div class="m">Hi! Ask me about shipping, returns or opening hours.</div></div><div class="d-flex gap-2"><input id="q" class="form-control" placeholder="Type a question"><button id="go" class="btn btn-cobalt" type="button">Send</button></div></div></div></section><script>(function(){var A=[[/ship|deliver/i,"Standard shipping takes 3–5 business days. Express is 1–2."],[/return|refund/i,"You can return items within 30 days for a full refund."],[/hour|open/i,"Our team is online 9am–6pm on weekdays."]],l=document.getElementById("log"),q=document.getElementById("q");function add(t,c){var d=document.createElement("div");d.className="m "+(c||"");d.textContent=t;l.appendChild(d);l.scrollTop=l.scrollHeight}function send(){var v=q.value.trim();if(!v)return;add(v,"u");q.value="";var a="I don't know that one, so I'll pass you to a teammate with the full context.";A.forEach(function(x){if(x[0].test(v))a=x[1]});setTimeout(function(){add(a)},500)}document.getElementById("go").onclick=send;q.onkeydown=function(e){if(e.key==="Enter")send()}})()</script>
@endsection
