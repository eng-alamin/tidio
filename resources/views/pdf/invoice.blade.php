<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e5e5; }
        .total-row td { font-weight: bold; border-top: 2px solid #1a1a1a; border-bottom: none; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
            <p class="muted">Issued: {{ optional($invoice->issued_at)->format('d M Y') }}</p>
        </div>
        <div style="text-align:right">
            <strong>{{ $invoice->workspace->name }}</strong><br>
            <span class="muted">Status: {{ $invoice->status->value }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ $invoice->subscription?->plan?->name ?? 'Subscription' }} plan
                    @if($invoice->coupon)
                        <br><span class="muted">Coupon applied: {{ $invoice->coupon->code }}</span>
                    @endif
                </td>
                <td>{{ number_format($invoice->amount / 100, 2) }} {{ $invoice->currency }}</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td>{{ number_format($invoice->amount / 100, 2) }} {{ $invoice->currency }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:40px">
        Paid: {{ optional($invoice->paid_at)->format('d M Y') ?? '—' }}
    </p>
</body>
</html>
