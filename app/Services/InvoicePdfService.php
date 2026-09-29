<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf; // composer require barryvdh/laravel-dompdf
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Renders resources/views/pdf/invoice.blade.php to PDF, stores it, and
     * updates invoices.pdf_url so it doesn't have to be regenerated on
     * every request. Call this from a queued job after an invoice is marked
     * paid, not synchronously in the request cycle.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['workspace', 'subscription.plan', 'coupon']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        $path = "invoices/{$invoice->workspace_id}/{$invoice->invoice_number}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $invoice->update(['pdf_url' => Storage::disk('public')->url($path)]);

        return $invoice->pdf_url;
    }

    public function download(Invoice $invoice)
    {
        if (! $invoice->pdf_url) {
            $this->generate($invoice);
        }

        $invoice->refresh();
        $path = "invoices/{$invoice->workspace_id}/{$invoice->invoice_number}.pdf";

        return Storage::disk('public')->download($path, "{$invoice->invoice_number}.pdf");
    }
}
