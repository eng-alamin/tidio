<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Billing</h2>

        <div class="card" style="max-width:520px;margin-bottom:22px">
            @if ($this->subscription)
                <p><b>{{ $this->subscription->plan_name ?? $this->subscription->plan?->name }}</b> plan
                    <span class="pill {{ $this->subscription->status->value === 'active' ? 'ok' : '' }}">
                        {{ ucfirst($this->subscription->status->value) }}
                    </span>
                </p>
                <p style="color:var(--soft)">
                    {{ $this->subscription->seats }} seat(s) ·
                    Renews {{ $this->subscription->renews_at?->format('M j, Y') ?? '—' }}
                </p>
            @else
                <p style="color:var(--soft)">No active subscription — you're on the free tier.</p>
            @endif
            <button class="btn" data-toast="Payment method — coming soon.">Manage payment method</button>
        </div>

        <h2 class="sec">Plans</h2>
        <div class="tpl" style="margin-bottom:22px">
            @foreach ($this->plans as $plan)
                <div class="card">
                    <p><b>{{ $plan->name }}</b></p>
                    <p style="font-size:22px;margin:4px 0">${{ number_format($plan->price_monthly / 100, 0) }}<small style="font-size:13px;color:var(--soft)">/mo</small></p>
                    <p style="color:var(--soft);font-size:13px">Up to {{ $plan->limits['operators'] ?? '—' }} operators · {{ $plan->limits['ai_conversations'] ?? '—' }} AI conversations</p>
                    <button class="btn {{ $this->subscription?->plan_id === $plan->id ? '' : 'pri' }}" data-toast="Plan checkout — coming soon.">
                        {{ $this->subscription?->plan_id === $plan->id ? 'Current plan' : 'Switch to '.$plan->name }}
                    </button>
                </div>
            @endforeach
        </div>

        <h2 class="sec">Invoices</h2>
        <div class="card" style="padding:0">
            <table>
                <tr><th>Invoice</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr>
                @forelse ($this->invoices as $invoice)
                    <tr wire:key="inv-{{ $invoice->id }}">
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ strtoupper($invoice->currency) }} {{ number_format($invoice->amount / 100, 2) }}</td>
                        <td><span class="pill {{ $invoice->status->value === 'paid' ? 'ok' : '' }}">{{ ucfirst($invoice->status->value) }}</span></td>
                        <td>{{ $invoice->issued_at?->format('M j, Y') }}</td>
                        <td>
                            @if ($invoice->pdf_url)
                                <a class="ib" href="{{ $invoice->pdf_url }}" target="_blank" rel="noopener" aria-label="Download PDF"><i class="bi bi-download"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--soft)">No invoices yet.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
