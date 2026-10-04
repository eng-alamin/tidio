<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\DiscountType;
use App\Enums\SuperAdminRole;
use App\Models\Coupon;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\CouponService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Coupons')]
class Coupons extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** active | scheduled | expired | exhausted | inactive */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** null | form | delete */
    public ?string $modal = null;

    public ?int $editingId = null;

    public string $code = '';

    public string $discountType = 'percent';

    /** Percent: whole number. Fixed: dollars with up to 2 decimals (stored in cents). */
    public string $discountValue = '';

    public string $validFrom = '';

    public string $validUntil = '';

    public string $maxRedemptions = '';

    public bool $isActive = true;

    // ---------------------------------------------------------------- data

    /**
     * Status is derived, in this order: inactive, expired, exhausted, scheduled, active.
     * Keep this in sync with status() below.
     */
    private function applyStatus(Builder $q, string $status): void
    {
        $now = now();

        $notExpired = fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('valid_until')->orWhere('valid_until', '>=', $now));
        $notExhausted = fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('max_redemptions')->orWhereColumn('times_redeemed', '<', 'max_redemptions'));

        switch ($status) {
            case 'inactive':
                $q->where('is_active', false);
                break;
            case 'expired':
                $q->where('is_active', true)->where('valid_until', '<', $now);
                break;
            case 'exhausted':
                $q->where('is_active', true);
                $notExpired($q);
                $q->whereNotNull('max_redemptions')->whereColumn('times_redeemed', '>=', 'max_redemptions');
                break;
            case 'scheduled':
                $q->where('is_active', true);
                $notExpired($q);
                $notExhausted($q);
                $q->where('valid_from', '>', $now);
                break;
            case 'active':
                $q->where('is_active', true);
                $notExpired($q);
                $notExhausted($q);
                $q->where(fn ($w) => $w->whereNull('valid_from')->orWhere('valid_from', '<=', $now));
                break;
        }
    }

    /** @return array{active:int, redemptions:int, expiring:int, ended:int} */
    #[Computed]
    public function stats(): array
    {
        $active = fn () => tap(Coupon::query(), fn ($q) => $this->applyStatus($q, 'active'));

        return [
            'active' => $active()->count(),
            'redemptions' => (int) Coupon::query()->sum('times_redeemed'),
            'expiring' => $active()->whereBetween('valid_until', [now(), now()->addDays(30)])->count(),
            'ended' => Coupon::query()->where(function (Builder $q) {
                $q->where('is_active', false)
                    ->orWhere('valid_until', '<', now());
            })->count(),
        ];
    }

    #[Computed]
    public function coupons(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Coupon::query()
            ->withCount(['invoices' => fn ($q) => $q->withTrashed()])
            ->when($search !== '', fn (Builder $q) => $q->where('code', 'like', '%'.$search.'%'))
            ->when($this->statusFilter !== '', fn (Builder $q) => $this->applyStatus($q, $this->statusFilter))
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function editing(): ?Coupon
    {
        return $this->editingId ? Coupon::query()->find($this->editingId) : null;
    }

    /** True when the open coupon has been redeemed or invoiced, so code and discount are read-only. */
    #[Computed]
    public function editingIsUsed(): bool
    {
        return $this->editing !== null && CouponService::isUsed($this->editing);
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

    /** @return array{label:string, class:string} */
    public function status(Coupon $coupon): array
    {
        if (! $coupon->is_active) {
            return ['label' => 'Inactive', 'class' => 'status-suspended'];
        }
        if ($coupon->valid_until && $coupon->valid_until->isPast()) {
            return ['label' => 'Expired', 'class' => 'status-past'];
        }
        if ($coupon->max_redemptions !== null && $coupon->times_redeemed >= $coupon->max_redemptions) {
            return ['label' => 'Used up', 'class' => 'status-past'];
        }
        if ($coupon->valid_from && $coupon->valid_from->isFuture()) {
            return ['label' => 'Scheduled', 'class' => 'status-trial'];
        }

        return ['label' => 'Active', 'class' => 'status-active'];
    }

    /** Fixed discounts are stored in cents; coupons carry no currency, so they display as USD. */
    public function discountLabel(Coupon $coupon): string
    {
        return $coupon->discount_type === DiscountType::Percent
            ? $coupon->discount_value.'% off'
            : '$'.number_format($coupon->discount_value / 100, 2).' off';
    }

    public function validityLabel(Coupon $coupon): string
    {
        $from = $coupon->valid_from?->format('Y-m-d');
        $until = $coupon->valid_until?->format('Y-m-d');

        return match (true) {
            $from && $until => "{$from} to {$until}",
            $until !== null => "Until {$until}",
            $from !== null => "From {$from}",
            default => 'No expiry',
        };
    }

    // ---------------------------------------------------------- permissions

    /** Super admins and billing admins create and edit; support staff is read-only. */
    public function canManage(): bool
    {
        return in_array($this->admin()->role, [SuperAdminRole::SuperAdmin, SuperAdminRole::BillingAdmin], true);
    }

    /** Deleting is the strictest tier, matching CouponPolicy. */
    public function canDelete(): bool
    {
        return $this->admin()->role === SuperAdminRole::SuperAdmin;
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    // -------------------------------------------------------------- modals

    public function openCreate(): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->modal = 'form';
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $coupon = Coupon::findOrFail($id);

        $this->resetForm();
        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->discountType = $coupon->discount_type->value;
        $this->discountValue = $coupon->discount_type === DiscountType::Percent
            ? (string) $coupon->discount_value
            : number_format($coupon->discount_value / 100, 2, '.', '');
        $this->validFrom = $coupon->valid_from?->format('Y-m-d') ?? '';
        $this->validUntil = $coupon->valid_until?->format('Y-m-d') ?? '';
        $this->maxRedemptions = $coupon->max_redemptions !== null ? (string) $coupon->max_redemptions : '';
        $this->isActive = $coupon->is_active;
        $this->modal = 'form';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);

        $this->resetForm();
        $this->editingId = Coupon::findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'discountValue', 'validFrom', 'validUntil', 'maxRedemptions']);
        $this->discountType = 'percent';
        $this->isActive = true;
        $this->resetValidation();
        unset($this->editing, $this->editingIsUsed);
    }

    // ------------------------------------------------------------- actions

    public function save(CouponService $service): void
    {
        abort_unless($this->canManage(), 403);

        $coupon = $this->editingId !== null ? Coupon::findOrFail($this->editingId) : null;
        $locked = $coupon !== null && CouponService::isUsed($coupon);

        // Frozen fields come from the database, never from the request.
        if ($locked) {
            $this->code = $coupon->code;
            $this->discountType = $coupon->discount_type->value;
            $this->discountValue = $coupon->discount_type === DiscountType::Percent
                ? (string) $coupon->discount_value
                : number_format($coupon->discount_value / 100, 2, '.', '');
        }

        $this->code = strtoupper(trim($this->code));

        $untilChanged = $coupon === null || $this->validUntil !== ($coupon->valid_until?->format('Y-m-d') ?? '');
        $minLimit = max(1, (int) ($coupon?->times_redeemed ?? 0));

        $untilRules = ['nullable', 'date'];
        if ($this->validFrom !== '') {
            $untilRules[] = 'after_or_equal:validFrom';
        }
        if ($untilChanged) {
            $untilRules[] = 'after_or_equal:today';
        }

        $rules = [
            'code' => [
                'required', 'string', 'min:3', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                // Includes soft-deleted rows on purpose: the DB unique index does too.
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'discountType' => ['required', Rule::in(['percent', 'fixed'])],
            'discountValue' => $this->discountType === 'percent'
                ? ['required', 'integer', 'between:1,100']
                : ['required', 'numeric', 'min:0.01', 'max:100000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'validFrom' => ['nullable', 'date'],
            'validUntil' => $untilRules,
            'maxRedemptions' => ['nullable', 'integer', "min:{$minLimit}", 'max:100000000'],
            'isActive' => ['boolean'],
        ];

        $this->validate($rules, [
            'code.regex' => 'Use capital letters, numbers, dashes and underscores only.',
            'code.unique' => 'That code already exists. Deleted coupons keep their code reserved.',
            'discountValue.between' => 'A percentage discount must be between 1 and 100.',
            'discountValue.integer' => 'Enter a whole percentage, for example 20.',
            'discountValue.regex' => 'Use at most two decimal places, for example 10.50.',
            'validUntil.after_or_equal' => 'The end date can\'t be before the start date or in the past.',
            'maxRedemptions.min' => $minLimit > 1
                ? "The limit can't be lower than the {$minLimit} redemptions already recorded."
                : 'The limit must be at least 1. Leave it empty for unlimited.',
        ]);

        $payload = [
            'code' => $this->code,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountType === 'percent'
                ? (int) $this->discountValue
                : (int) round(((float) $this->discountValue) * 100),
            'valid_from' => $this->validFrom !== '' ? Carbon::parse($this->validFrom)->startOfDay() : null,
            'valid_until' => $this->validUntil !== '' ? Carbon::parse($this->validUntil)->endOfDay() : null,
            'max_redemptions' => $this->maxRedemptions !== '' ? (int) $this->maxRedemptions : null,
            'is_active' => $this->isActive,
        ];

        try {
            if ($coupon) {
                $service->update($this->admin(), $coupon, $payload);
            } else {
                $service->create($this->admin(), $payload);
            }
        } catch (UniqueConstraintViolationException) {
            $this->addError('code', 'That code already exists. Deleted coupons keep their code reserved.');

            return;
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not save the coupon. Please try again.', type: 'error');

            return;
        }

        if (! $coupon) {
            $this->resetPage();
        }

        $this->dispatch('toast', message: $coupon ? "{$this->code} updated." : "{$this->code} created.", type: 'ok');
        $this->closeModal();
    }

    public function toggleActive(int $id, CouponService $service): void
    {
        abort_unless($this->canManage(), 403);

        $coupon = Coupon::findOrFail($id);
        $makeActive = ! $coupon->is_active;

        try {
            $service->setActive($this->admin(), $coupon, $makeActive);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the coupon.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: $makeActive ? "{$coupon->code} activated." : "{$coupon->code} deactivated.", type: $makeActive ? 'ok' : 'warn');
    }

    public function delete(CouponService $service): void
    {
        abort_unless($this->canDelete(), 403);

        $coupon = Coupon::findOrFail($this->editingId);

        try {
            $service->delete($this->admin(), $coupon);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the coupon.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$coupon->code} deleted.", type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.coupons');
    }
}
