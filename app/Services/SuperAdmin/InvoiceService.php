<?php

namespace App\Services\SuperAdmin;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Status changes on invoices made from the Super Admin panel.
 * Allowed transitions are enforced here, not in the UI.
 */
class InvoiceService
{
    /** Statuses an invoice can still be paid from. */
    private const PAYABLE = [InvoiceStatus::Draft, InvoiceStatus::Open, InvoiceStatus::Uncollectible];

    /** Statuses an invoice can still be voided from. */
    private const VOIDABLE = [InvoiceStatus::Draft, InvoiceStatus::Open];

    public static function canMarkPaid(Invoice $invoice): bool
    {
        return in_array($invoice->status, self::PAYABLE, true);
    }

    public static function canVoid(Invoice $invoice): bool
    {
        return in_array($invoice->status, self::VOIDABLE, true);
    }

    /**
     * @throws RuntimeException when the invoice is not payable any more
     * @throws Throwable
     */
    public function markPaid(SuperAdmin $actor, Invoice $invoice): void
    {
        if (! self::canMarkPaid($invoice)) {
            throw new RuntimeException("A {$invoice->status->value} invoice can't be marked as paid.");
        }

        $this->transition($actor, $invoice, InvoiceStatus::Paid, 'Invoice marked as paid', ['paid_at' => now()]);
    }

    /**
     * @throws RuntimeException when the invoice is not voidable any more
     * @throws Throwable
     */
    public function void(SuperAdmin $actor, Invoice $invoice): void
    {
        if (! self::canVoid($invoice)) {
            throw new RuntimeException("A {$invoice->status->value} invoice can't be voided.");
        }

        $this->transition($actor, $invoice, InvoiceStatus::Void, 'Invoice voided');
    }

    /**
     * @param  array<string, mixed>  $extra
     *
     * @throws Throwable
     */
    private function transition(SuperAdmin $actor, Invoice $invoice, InvoiceStatus $to, string $message, array $extra = []): void
    {
        $from = $invoice->status;

        DB::beginTransaction();

        try {
            $invoice->update(['status' => $to] + $extra);

            activity('invoice')
                ->causedBy($actor)
                ->performedOn($invoice)
                ->withProperties([
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency,
                    'from' => $from?->value,
                    'to' => $to->value,
                ])
                ->log($message);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
