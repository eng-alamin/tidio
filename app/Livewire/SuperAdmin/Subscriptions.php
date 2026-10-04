<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Enums\SuperAdminRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Subscriptions')]
class Subscriptions extends Component
{
    use WithPagination;

    private const CANCELLED_WINDOW_DAYS = 30;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'plan', except: '')]
    public string $planFilter = '';

    /** null | form | cancel */
    public ?string $modal = null;

    public ?int $editingId = null;

    public ?int $planId = null;

    public string $billingCycle = 'monthly';

    public ?int $seats = 1;

    public string $status = 'active';

    public string $renewsAt = '';

    // ---------------------------------------------------------------- data

    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()->orderBy('sort_order')->get(['id', 'name', 'slug']);
    }

    /**
     * Base query: the CURRENT subscription (highest id) of every live workspace.
     * Older rows of the same workspace are history and are not listed.
     */
    private function currentSubscriptions()
    {
        return Subscription::query()
            ->whereIn('subscriptions.id', fn ($q) => $q
                ->selectRaw('MAX(id)')
                ->from('subscriptions')
                ->whereNull('deleted_at')
                ->groupBy('workspace_id'))
            ->whereHas('workspace');
    }

    /** @return array{active:int, trialing:int, past_due:int, cancelled:int} */
    #[Computed]
    public function stats(): array
    {
        $counts = $this->currentSubscriptions()
            ->selectRaw('subscriptions.status, COUNT(*) as total')
            ->groupBy('subscriptions.status')
            ->pluck('total', 'status');

        $cancelledRecently = $this->currentSubscriptions()
            ->where('subscriptions.status', SubscriptionStatus::Cancelled->value)
            ->where('subscriptions.updated_at', '>=', now()->subDays(self::CANCELLED_WINDOW_DAYS))
            ->count();

        return [
            'active' => (int) ($counts[SubscriptionStatus::Active->value] ?? 0),
            'trialing' => (int) ($counts[SubscriptionStatus::Trialing->value] ?? 0),
            'past_due' => (int) ($counts[SubscriptionStatus::PastDue->value] ?? 0),
            'cancelled' => $cancelledRecently,
        ];
    }

    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return $this->currentSubscriptions()
            ->with(['workspace:id,name,is_suspended', 'plan:id,name,slug,price_monthly,price_yearly'])
            ->when($search !== '', fn ($q) => $q->whereHas(
                'workspace',
                fn ($w) => $w->where('name', 'like', '%'.$search.'%')
            ))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('subscriptions.status', $this->statusFilter))
            ->when($this->planFilter !== '', fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('slug', $this->planFilter)))
            ->latest('subscriptions.id')
            ->paginate(10);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPlanFilter(): void
    {
        $this->resetPage();
    }

    // ---------------------------------------------------------- permissions

    /** Super admins and billing admins manage subscriptions; support staff is read-only. */
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

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $subscription = $this->findManageable($id);

        $this->resetForm();
        $this->editingId = $subscription->id;
        $this->planId = $subscription->plan_id ?? $this->plans->first()?->id;
        $this->billingCycle = $subscription->billing_cycle?->value ?? BillingCycle::Monthly->value;
        $this->seats = (int) $subscription->seats;
        $this->status = $subscription->status->value;
        $this->renewsAt = $subscription->renews_at?->format('Y-m-d') ?? '';
        $this->modal = 'form';
    }

    public function confirmCancel(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = $this->findManageable($id)->id;
        $this->modal = 'cancel';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    #[Computed]
    public function editingSubscription(): ?Subscription
    {
        return $this->editingId
            ? Subscription::with('workspace:id,name')->find($this->editingId)
            : null;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'planId', 'seats', 'renewsAt']);
        $this->billingCycle = BillingCycle::Monthly->value;
        $this->status = SubscriptionStatus::Active->value;
        $this->resetValidation();
        unset($this->editingSubscription);
    }

    /** Only subscriptions of live workspaces can be edited from the panel. */
    private function findManageable(int $id): Subscription
    {
        return Subscription::query()->whereHas('workspace')->findOrFail($id);
    }

    // ------------------------------------------------------------- actions

    public function save(SubscriptionService $service): void
    {
        abort_unless($this->canManage(), 403);

        $subscription = $this->findManageable((int) $this->editingId);

        $statuses = array_column(SubscriptionStatus::cases(), 'value');
        $cycles = array_column(BillingCycle::cases(), 'value');
        $needsRenewal = $this->status !== SubscriptionStatus::Cancelled->value;

        $this->validate([
            'planId' => ['required', 'integer', Rule::exists('plans', 'id')],
            'billingCycle' => ['required', Rule::in($cycles)],
            'seats' => ['required', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::in($statuses)],
            'renewsAt' => [$needsRenewal ? 'required' : 'nullable', 'date_format:Y-m-d'],
        ]);

        try {
            $service->update($this->admin(), $subscription, [
                'plan_id' => (int) $this->planId,
                'billing_cycle' => $this->billingCycle,
                'seats' => (int) $this->seats,
                'status' => $this->status,
                'renews_at' => $this->renewsAt !== '' ? Carbon::createFromFormat('Y-m-d', $this->renewsAt)->startOfDay() : null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the subscription. Please try again.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: 'Subscription updated.', type: 'ok');
        $this->closeModal();
    }

    public function cancel(SubscriptionService $service): void
    {
        abort_unless($this->canManage(), 403);

        $subscription = $this->findManageable((int) $this->editingId);

        try {
            $service->cancel($this->admin(), $subscription);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not cancel the subscription.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: 'Subscription cancelled.', type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.subscriptions');
    }
}
