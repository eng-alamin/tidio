<?php

namespace App\Services;

use App\Models\UsageCounter;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Plan limits + monthly usage counters in one place.
 *
 * Limits come from the workspace's plan (`plans.limits`, e.g. {"ai_conversations": 500}). A metric
 * missing from the plan — or a null value — means unlimited; 0 means "not included in this plan".
 * Counters are calendar-month rows in `usage_counters`, the same rows the Usage page and the
 * Dashboard already read.
 *
 * Metrics used so far: `conversations` (every new conversation), `ai_conversations`
 * (conversations Lyro replied in, counted once per conversation).
 */
class UsageLimiter
{
    /** Same fallback the Usage page and Dashboard use when a workspace has no plan yet. */
    public const DEFAULT_LIMITS = ['ai_conversations' => 50];

    public function limit(Workspace $workspace, string $metric): ?int
    {
        $limits = $workspace->activeSubscription?->plan?->limits ?? self::DEFAULT_LIMITS;

        return isset($limits[$metric]) ? (int) $limits[$metric] : null;
    }

    public function used(Workspace $workspace, string $metric): int
    {
        return (int) UsageCounter::query()
            ->where('workspace_id', $workspace->id)
            ->where('metric', $metric)
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->sum('count');
    }

    /** True while the workspace is still under its limit (or has none). */
    public function hasRoom(Workspace $workspace, string $metric): bool
    {
        $limit = $this->limit($workspace, $metric);

        return $limit === null || $this->used($workspace, $metric) < $limit;
    }

    public function record(Workspace $workspace, string $metric, int $by = 1): void
    {
        $start = now()->startOfMonth();

        // UsageCounter casts period_start to a date, which Eloquent stores as "Y-m-d 00:00:00".
        // Looking the row up with a plain "Y-m-d" string would never match it, so match with
        // whereDate() and create the row with the same stored format. insertOrIgnore makes two
        // simultaneous first-of-the-month requests safe (the unique index drops the loser).
        $find = fn () => UsageCounter::query()
            ->where('workspace_id', $workspace->id)
            ->where('metric', $metric)
            ->whereDate('period_start', $start->toDateString())
            ->first();

        $counter = $find();

        if (! $counter) {
            DB::table('usage_counters')->insertOrIgnore([
                'workspace_id' => $workspace->id,
                'metric' => $metric,
                'count' => 0,
                'period_start' => $start->format('Y-m-d H:i:s'),
                'period_end' => now()->endOfMonth()->startOfDay()->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = $find();
        }

        $counter?->increment('count', $by); // atomic UPDATE ... SET count = count + n
    }
}