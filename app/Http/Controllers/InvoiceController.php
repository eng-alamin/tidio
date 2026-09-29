<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdfService;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice, InvoicePdfService $pdfService)
    {
        $this->authorize('manageBilling', $invoice->workspace);

        return $pdfService->download($invoice);
    }
}
