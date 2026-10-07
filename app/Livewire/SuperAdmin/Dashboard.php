<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SuperAdmin\MrrSnapshotService;
use Illuminate\Support\Collection;
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

    private const TREND_DAYS = 30;

    private const CHART_WIDTH = 600;

    private const CHART_HEIGHT = 200;

    public function mount(MrrSnapshotService $snapshots): void
    {
        // Makes sure today's data point exists, so the trend chart fills without waiting for the scheduler.
        $snapshots->ensureToday();
    }

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

    /** Monthly recurring revenue in whole currency units (see MrrSnapshotService::current()). */
    #[Computed]
    public function mrr(): float
    {
        return app(MrrSnapshotService::class)->current()['mrr_cents'] / 100;
    }

    /**
     * Revenue trend of the last 30 days, drawn as an inline SVG line. Null until there are two data points.
     *
     * @return array{line:string, area:string, dots:list<array{x:float,y:float,title:string}>, from:string, to:string, min:string, max:string, change:?float}|null
     */
    #[Computed]
    public function revenueTrend(): ?array
    {
        $series = app(MrrSnapshotService::class)->series(self::TREND_DAYS);

        if ($series->count() < 2) {
            return null;
        }

        $firstDate = $series->first()->snapshot_date;
        $span = max(1, (int) $firstDate->diffInDays($series->last()->snapshot_date));
        $min = (int) $series->min('mrr_cents');
        $max = (int) $series->max('mrr_cents');
        $range = max(1, $max - $min);

        $padX = 8;
        $top = 16;
        $bottom = 24;
        $width = self::CHART_WIDTH - 2 * $padX;
        $height = self::CHART_HEIGHT - $top - $bottom;

        $dots = $series->map(function ($snapshot) use ($firstDate, $span, $min, $max, $range, $padX, $top, $width, $height) {
            $x = $padX + ((int) $firstDate->diffInDays($snapshot->snapshot_date) / $span) * $width;
            $y = $max === $min
                ? $top + $height / 2
                : $top + (1 - ($snapshot->mrr_cents - $min) / $range) * $height;

            return [
                'x' => round($x, 1),
                'y' => round($y, 1),
                'title' => $snapshot->snapshot_date->format('M j').': $'.number_format($snapshot->mrr_cents / 100, 0),
            ];
        })->values();

        $baseline = $top + $height;
        $line = $dots->map(fn ($d) => $d['x'].','.$d['y'])->implode(' ');
        $area = 'M'.$dots->first()['x'].','.$baseline.' L'.$dots->map(fn ($d) => $d['x'].','.$d['y'])->implode(' L')
            .' L'.$dots->last()['x'].','.$baseline.' Z';

        $firstCents = (int) $series->first()->mrr_cents;
        $lastCents = (int) $series->last()->mrr_cents;

        return [
            'line' => $line,
            'area' => $area,
            'dots' => $dots->all(),
            'from' => $firstDate->format('M j'),
            'to' => $series->last()->snapshot_date->format('M j'),
            'min' => '$'.number_format($min / 100, 0),
            'max' => '$'.number_format($max / 100, 0),
            'change' => $firstCents > 0 ? round(($lastCents - $firstCents) / $firstCents * 100, 1) : null,
        ];
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
