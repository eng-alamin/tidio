<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Usage this period</h2>

        <div class="card" style="max-width:520px">
            @foreach ($this->metrics as $metric)
                @php($pct = $metric['limit'] ? min(100, round($metric['used'] / max($metric['limit'], 1) * 100)) : 0)
                <p style="margin-top:{{ $loop->first ? 0 : '18px' }}">
                    <b>{{ $metric['label'] }}</b><br>
                    {{ $metric['used'] }} / {{ $metric['limit'] ?? '∞' }}
                </p>
                @if ($metric['limit'])
                    <div class="bar"><div style="width:{{ $pct }}%;height:100%;border-radius:8px;background:{{ $pct >= 90 ? 'var(--bad)' : 'var(--cobalt)' }}"></div></div>
                @endif
            @endforeach
        </div>
    </div>
</div>
