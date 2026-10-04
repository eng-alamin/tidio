<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.super-admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    private const CHURN_WINDOW_DAYS = 30;

    private const PLAN_COLORS = ['var(--cobalt)', 'var(--citrus)', 'var(--violet)', 'var(--ok)'];

    #[Computed]
    public function totalTenants(): int
    {
        return Workspace::count();
    }

    #[Computed]
    public function newTenantsThisMonth(): int
    {
        return Workspace::where('created_at', '>=', now()->startOfMonth())->count();
    }

    /**
     * Monthly recurring revenue in whole currency units: every active subscription
     * whose workspace still exists, yearly plans spread over 12 months.
     */
    #[Computed]
    public function mrr(): float
    {
        $cents = Subscription::query()
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.status', SubscriptionStatus::Active->value)
            ->whereHas('workspace')
            ->sum(DB::raw("CASE WHEN subscriptions.billing_cycle = 'yearly' THEN plans.price_yearly / 12 ELSE plans.price_monthly END"));

        return $cents / 100;
    }

    #[Computed]
    public function activeUsers(): int
    {
        return User::query()
            ->where('is_disabled', false)
            ->whereHas('workspaces', fn ($q) => $q->where('workspace_user.status', 'active'))
            ->count();
    }

    /**
     * Approximation: subscriptions cancelled in the last 30 days divided by
     * (still-live subscriptions + those churned subscriptions).
     */
    #[Computed]
    public function churnRate(): float
    {
        $churned = Subscription::query()
            ->where('status', SubscriptionStatus::Cancelled->value)
            ->where('updated_at', '>=', now()->subDays(self::CHURN_WINDOW_DAYS))
            ->count();

        $live = Subscription::query()
            ->whereIn('status', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->whereHas('workspace')
            ->count();

        $base = $live + $churned;

        return $base > 0 ? round($churned / $base * 100, 1) : 0.0;
    }

    /**
     * @return Collection<int, array{name:string, count:int, percent:int, color:string}>
     */
    #[Computed]
    public function planDistribution(): Collection
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->withCount(['subscriptions as tenants_count' => fn ($q) => $q
                ->whereIn('status', [
                    SubscriptionStatus::Trialing->value,
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::PastDue->value,
                ])
                ->whereHas('workspace')])
            ->orderBy('sort_order')
            ->get();

        $total = max(1, $plans->sum('tenants_count'));

        return $plans->values()->map(fn (Plan $plan, int $i) => [
            'name' => $plan->name,
            'count' => (int) $plan->tenants_count,
            'percent' => (int) round($plan->tenants_count / $total * 100),
            'color' => self::PLAN_COLORS[$i % count(self::PLAN_COLORS)],
        ]);
    }

    #[Computed]
    public function recentTenants(): Collection
    {
        return Workspace::query()
            ->with(['activeSubscription.plan'])
            ->withCount(['users as agents_count' => fn ($q) => $q->where('workspace_user.status', 'active')])
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.super-admin.dashboard');
    }
}
