<?php

namespace App\Services\SuperAdmin;

use App\Models\ConversationMetric;
use App\Models\Conversation;
use App\Models\CsatRating;
use App\Models\Message;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Platform-wide numbers for the Super Admin "Analytics" page (all tenants combined).
 */
class AnalyticsService
{
    public const WEEKS = 12;

    public const KPI_DAYS = 30;

    /**
     * New workspaces per week, oldest week first.
     *
     * @return list<array{label:string, count:int}>
     */
    public function weeklySignups(int $weeks = self::WEEKS): array
    {
        return $this->weekly(Workspace::query(), $weeks);
    }

    /**
     * Support tickets opened per week across all tenants, oldest week first.
     *
     * @return list<array{label:string, count:int}>
     */
    public function weeklyTickets(int $weeks = self::WEEKS): array
    {
        return $this->weekly(Conversation::query()->where('type', 'ticket'), $weeks);
    }

    /**
     * @return array{
     *     first_response:?string, csat:?float, csat_count:int, messages_per_day:int,
     *     resolution_rate:?int, conversations:int, days:int
     * }
     */
    public function kpis(int $days = self::KPI_DAYS): array
    {
        $since = now()->subDays($days);

        $avgFirstResponse = ConversationMetric::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('first_response_seconds')
            ->avg('first_response_seconds');

        $csat = CsatRating::query()->where('created_at', '>=', $since);
        $csatCount = (clone $csat)->count();
        $csatAvg = $csatCount > 0 ? round((float) (clone $csat)->avg('rating'), 1) : null;

        $messages = Message::query()
            ->where('created_at', '>=', $since)
            ->where('is_private_note', false)
            ->count();

        $totals = Conversation::query()
            ->where('created_at', '>=', $since)
            ->where('status', '!=', 'spam')
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'solved' THEN 1 ELSE 0 END) as solved")
            ->first();

        $total = (int) ($totals->total ?? 0);
        $solved = (int) ($totals->solved ?? 0);

        return [
            'first_response' => $avgFirstResponse !== null ? $this->duration((int) round((float) $avgFirstResponse)) : null,
            'csat' => $csatAvg,
            'csat_count' => $csatCount,
            'messages_per_day' => (int) round($messages / max(1, $days)),
            'resolution_rate' => $total > 0 ? (int) round($solved / $total * 100) : null,
            'conversations' => $total,
            'days' => $days,
        ];
    }

    /** 102 => "1m 42s", 45 => "45s", 3700 => "1h 1m" */
    public function duration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }
        if ($seconds < 3600) {
            return intdiv($seconds, 60).'m '.($seconds % 60).'s';
        }

        return intdiv($seconds, 3600).'h '.intdiv($seconds % 3600, 60).'m';
    }

    /**
     * Buckets rows of the given query into calendar weeks (Monday start) by created_at.
     * Done in PHP on a single narrow query, so it behaves the same on MySQL and SQLite.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return list<array{label:string, count:int}>
     */
    private function weekly(Builder $query, int $weeks): array
    {
        $first = now()->startOfWeek()->subWeeks($weeks - 1);

        $buckets = [];
        for ($i = 0; $i < $weeks; $i++) {
            $start = $first->copy()->addWeeks($i);
            $buckets[] = ['label' => $start->format('M j'), 'count' => 0];
        }

        $firstTs = $first->getTimestamp();

        foreach ($query->where('created_at', '>=', $first)->select('created_at')->cursor() as $row) {
            $index = intdiv(Carbon::parse($row->created_at)->getTimestamp() - $firstTs, 7 * 86400);

            if (isset($buckets[$index])) {
                $buckets[$index]['count']++;
            }
        }

        return $buckets;
    }
}
