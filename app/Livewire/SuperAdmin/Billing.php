<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\InvoiceStatus;
use App\Enums\SuperAdminRole;
use App\Models\Invoice;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\InvoiceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Billing & Invoices')]
class Billing extends Component
{
    use WithPagination;

    private const STATS_CURRENCY = 'USD';

    private const STATS_WINDOW_DAYS = 30;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Any InvoiceStatus value, or "overdue" (open and past its due date). */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** null | view | pay | void */
    public ?string $modal = null;

    public ?int $invoiceId = null;

    // ---------------------------------------------------------------- data

    private function filteredQuery(): Builder
    {
        $search = trim($this->search);

        return Invoice::query()
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $q) => $q->where('invoice_number', 'like', $like)
                    ->orWhereHas('workspace', fn ($w) => $w->withTrashed()->where('name', 'like', $like)));
            })
            ->when($this->statusFilter === 'overdue', fn (Builder $q) => $q
                ->where('status', InvoiceStatus::Open->value)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()))
            ->when(
                $this->statusFilter !== '' && $this->statusFilter !== 'overdue',
                fn (Builder $q) => $q->where('status', $this->statusFilter)
            );
    }

    /** @return array{collected:float, outstanding:float, overdue:int, this_month:int} */
    #[Computed]
    public function stats(): array
    {
        $usd = fn () => Invoice::query()->where('currency', self::STATS_CURRENCY);

        $collected = $usd()
            ->where('status', InvoiceStatus::Paid->value)
            ->where('paid_at', '>=', now()->subDays(self::STATS_WINDOW_DAYS))
            ->sum('amount');

        $outstanding = $usd()->where('status', InvoiceStatus::Open->value)->sum('amount');

        $overdue = Invoice::query()
            ->where('status', InvoiceStatus::Open->value)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $thisMonth = Invoice::query()->where('created_at', '>=', now()->startOfMonth())->count();

        return [
            'collected' => $collected / 100,
            'outstanding' => $outstanding / 100,
            'overdue' => $overdue,
            'this_month' => $thisMonth,
        ];
    }

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return $this->filteredQuery()
            ->with(['workspace' => fn ($q) => $q->withTrashed()->select('id', 'name', 'deleted_at')])
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function selected(): ?Invoice
    {
        return $this->invoiceId
            ? Invoice::with([
                'workspace' => fn ($q) => $q->withTrashed()->select('id', 'name', 'deleted_at'),
                'subscription:id,plan_name',
                'coupon:id,code',
            ])->find($this->invoiceId)
            : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ------------------------------------------------------------ presenters

    public function money(Invoice $invoice): string
    {
        $amount = number_format($invoice->amount / 100, 2);

        return $invoice->currency === 'USD' ? '$'.$amount : $invoice->currency.' '.$amount;
    }

    /** @return array{label:string, class:string} */
    public function badge(Invoice $invoice): array
    {
        $overdue = $invoice->status === InvoiceStatus::Open
            && $invoice->due_at
            && $invoice->due_at->isPast();

        if ($overdue) {
            return ['label' => 'Overdue', 'class' => 'status-past'];
        }

        return match ($invoice->status) {
            InvoiceStatus::Paid => ['label' => 'Paid', 'class' => 'status-active'],
            InvoiceStatus::Open => ['label' => 'Open', 'class' => 'status-trial'],
            InvoiceStatus::Uncollectible => ['label' => 'Uncollectible', 'class' => 'status-past'],
            InvoiceStatus::Void => ['label' => 'Void', 'class' => 'status-suspended'],
            default => ['label' => 'Draft', 'class' => 'status-suspended'],
        };
    }

    /** Only http(s) links are rendered, so a bad `pdf_url` can never become a javascript: link. */
    public function safePdfUrl(Invoice $invoice): ?string
    {
        $url = (string) $invoice->pdf_url;

        return preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    public function canMarkPaid(Invoice $invoice): bool
    {
        return $this->canManage() && InvoiceService::canMarkPaid($invoice);
    }

    public function canVoid(Invoice $invoice): bool
    {
        return $this->canManage() && InvoiceService::canVoid($invoice);
    }

    // ---------------------------------------------------------- permissions

    /** Super admins and billing admins manage invoices; support staff is read-only. */
    public function canManage(): bool
    {
        return in_array($this->admin()->role, [SuperAdminRole::SuperAdmin, SuperAdminRole::BillingAdmin], true);
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    // -------------------------------------------------------------- modals

    public function openView(int $id): void
    {
        $this->open('view', $id);
    }

    public function confirmPay(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $this->open('pay', $id);
    }

    public function confirmVoid(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $this->open('void', $id);
    }

    private function open(string $modal, int $id): void
    {
        $this->invoiceId = Invoice::findOrFail($id)->id;
        unset($this->selected);
        $this->modal = $modal;
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->invoiceId = null;
        unset($this->selected);
    }

    // ------------------------------------------------------------- actions

    public function markPaid(InvoiceService $service): void
    {
        abort_unless($this->canManage(), 403);

        $invoice = Invoice::findOrFail($this->invoiceId);

        try {
            $service->markPaid($this->admin(), $invoice);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the invoice.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$invoice->invoice_number} marked as paid.", type: 'ok');
        $this->closeModal();
    }

    public function voidInvoice(InvoiceService $service): void
    {
        abort_unless($this->canManage(), 403);

        $invoice = Invoice::findOrFail($this->invoiceId);

        try {
            $service->void($this->admin(), $invoice);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the invoice.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$invoice->invoice_number} voided.", type: 'warn');
        $this->closeModal();
    }

    /** Streams all invoices matching the current search/status filter as CSV. */
    public function exportCsv(): StreamedResponse
    {
        abort_unless($this->canManage(), 403);

        $query = $this->filteredQuery()
            ->with(['workspace' => fn ($q) => $q->withTrashed()->select('id', 'name', 'deleted_at')])
            ->orderBy('id');

        activity('invoice')
            ->causedBy($this->admin())
            ->withProperties(['search' => $this->search, 'status' => $this->statusFilter])
            ->log('Invoices exported to CSV');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Tenant', 'Amount', 'Currency', 'Status', 'Issued', 'Due', 'Paid']);

            foreach ($query->lazyById(500) as $invoice) {
                fputcsv($out, array_map($this->csvSafe(...), [
                    $invoice->invoice_number,
                    $invoice->workspace?->name ?? '',
                    number_format($invoice->amount / 100, 2, '.', ''),
                    $invoice->currency,
                    $invoice->status?->value ?? '',
                    $invoice->issued_at?->toDateString() ?? '',
                    $invoice->due_at?->toDateString() ?? '',
                    $invoice->paid_at?->toDateString() ?? '',
                ]));
            }

            fclose($out);
        }, 'invoices-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralises spreadsheet formula injection in user-controlled text (tenant names). */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    public function render()
    {
        return view('livewire.super-admin.billing');
    }
}
