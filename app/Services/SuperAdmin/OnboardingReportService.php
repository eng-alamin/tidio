<?php

namespace App\Services\SuperAdmin;

use App\Models\Channel;
use App\Models\EmailDomain;
use App\Models\Flow;
use App\Models\OnboardingProgress;
use App\Models\Website;
use App\Support\OnboardingSteps;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only report on how far each tenant got with the setup checklist.
 * Step state is derived from live data (the same rules the app Dashboard uses), because
 * `onboarding_progress` rows only appear once a tenant opened its own dashboard.
 * Uses one query per step for the whole platform, so it never loops per tenant.
 */
class OnboardingReportService
{
    /** @var array<string, array<int, true>>|null */
    private ?array $sets = null;

    /**
     * For each step key, the workspace ids that completed it.
     *
     * @return array<string, array<int, true>>
     */
    public function completedSets(): array
    {
        if ($this->sets !== null) {
            return $this->sets;
        }

        $ids = fn ($query) => $query->distinct()->pluck('workspace_id')->mapWithKeys(fn ($id) => [(int) $id => true])->all();

        return $this->sets = [
            'install_widget' => $ids(Website::query()->whereNotNull('installed_at')),
            'connect_mailbox' => $ids(Channel::query()->where('type', 'email')->where('status', 'connected')),
            'connect_domain' => $ids(EmailDomain::query()->where('status', 'verified')),
            'invite_team' => DB::table('workspace_user')
                ->select('workspace_id')
                ->groupBy('workspace_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('workspace_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all(),
            'create_flow' => $ids(Flow::query()),
        ];
    }

    /** @return array<int, int> workspace id => number of completed steps (only tenants with at least one) */
    public function progressCounts(): array
    {
        $counts = [];

        foreach ($this->completedSets() as $set) {
            foreach (array_keys($set) as $workspaceId) {
                $counts[$workspaceId] = ($counts[$workspaceId] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * Workspace ids that match a status filter. Null means "no filter".
     *
     * @return array{ids:list<int>, exclude:bool}|null  exclude=true means "every tenant except these ids"
     */
    public function idsForStatus(string $status): ?array
    {
        $counts = $this->progressCounts();
        $total = OnboardingSteps::total();

        return match ($status) {
            'complete' => ['ids' => array_keys(array_filter($counts, fn (int $c) => $c >= $total)), 'exclude' => false],
            'in_progress' => ['ids' => array_keys(array_filter($counts, fn (int $c) => $c > 0 && $c < $total)), 'exclude' => false],
            'not_started' => ['ids' => array_keys($counts), 'exclude' => true],
            default => null,
        };
    }

    /**
     * Completion per step across the whole platform.
     *
     * @return list<array{key:string, label:string, icon:string, color:string, count:int, percent:int}>
     */
    public function stepStats(int $totalTenants): array
    {
        $sets = $this->completedSets();
        $stats = [];

        foreach (OnboardingSteps::STEPS as $key => $meta) {
            $count = count($sets[$key] ?? []);

            $stats[] = $meta + [
                'key' => $key,
                'count' => $count,
                'percent' => $totalTenants > 0 ? (int) round($count / $totalTenants * 100) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Latest recorded step completion for the given tenants.
     *
     * @param  list<int>  $workspaceIds
     * @return array<int, Carbon>
     */
    public function lastActivity(array $workspaceIds): array
    {
        if ($workspaceIds === []) {
            return [];
        }

        return OnboardingProgress::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->whereNotNull('completed_at')
            ->groupBy('workspace_id')
            ->selectRaw('workspace_id, MAX(completed_at) as last_at')
            ->pluck('last_at', 'workspace_id')
            ->map(fn ($value) => Carbon::parse($value))
            ->all();
    }
}
