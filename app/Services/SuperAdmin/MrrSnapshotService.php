<?php

namespace App\Services\SuperAdmin;

use App\Enums\SubscriptionStatus;
use App\Models\MrrSnapshot;
use App\Models\Subscription;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calculates the platform's monthly recurring revenue and keeps one snapshot per day,
 * so the Super Admin dashboard can draw a real revenue trend.
 */
class MrrSnapshotService
{
    /**
     * MRR right now: every active subscription whose workspace still exists,
     * yearly plans spread over 12 months. Amounts are in cents.
     *
     * @return array{mrr_cents:int, active_subscriptions:int, tenants_count:int}
     */
    public function current(): array
    {
        $row = Subscription::query()
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.status', SubscriptionStatus::Active->value)
            ->whereHas('workspace')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN subscriptions.billing_cycle = 'yearly' THEN plans.price_yearly / 12 ELSE plans.price_monthly END), 0) as cents, COUNT(*) as subs"
            )
            ->first();

        return [
            'mrr_cents' => (int) round((float) ($row->cents ?? 0)),
            'active_subscriptions' => (int) ($row->subs ?? 0),
            'tenants_count' => Workspace::count(),
        ];
    }

    /** Stores (or refreshes) the snapshot of one day. Safe to run many times a day. */
    public function record(?CarbonInterface $date = null): MrrSnapshot
    {
        $date = ($date ?? now())->toDateString();
        $figures = $this->current();

        return DB::transaction(function () use ($date, $figures) {
            $snapshot = MrrSnapshot::query()->updateOrCreate(['snapshot_date' => $date], $figures);

            if ($snapshot->wasRecentlyCreated) {
                activity('mrr')
                    ->performedOn($snapshot)
                    ->withProperties(['date' => $date] + $figures)
                    ->log('MRR snapshot recorded');
            }

            return $snapshot;
        });
    }

    /** Makes sure today has a snapshot, so the chart starts filling without waiting for the scheduler. */
    public function ensureToday(): void
    {
        if (! MrrSnapshot::query()->whereDate('snapshot_date', now()->toDateString())->exists()) {
            $this->record();
        }
    }

    /**
     * Snapshots of the last $days days, oldest first.
     *
     * @return Collection<int, MrrSnapshot>
     */
    public function series(int $days = 30): Collection
    {
        return MrrSnapshot::query()
            ->where('snapshot_date', '>=', now()->subDays(max(1, $days) - 1)->toDateString())
            ->orderBy('snapshot_date')
            ->get();
    }
}
