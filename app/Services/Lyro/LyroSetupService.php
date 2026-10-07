<?php

namespace App\Services\Lyro;

use App\Enums\AiDataSourceStatus;
use App\Models\AiAgentSetting;
use App\Models\AiDataSource;
use App\Models\Workspace;
use App\Services\LyroAiEngine;
use App\Services\UsageLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Everything behind Lyro > Setup: the 4-step checklist computed from the workspace's real data,
 * and the two actions that change state (Go live / Pause, and "a real Playground test happened").
 *
 *   1. Add data sources     done when at least one source has been read (status "synced")
 *   2. Test in playground   done once a real AI reply was produced in the Playground
 *   3. Choose channels      done once the Channels tab was saved with live chat and/or email on
 *   4. Go live              done while ai_agent_settings.is_active = true. This flag is the exact
 *                           switch LyroWidgetResponder checks before it answers a visitor.
 *
 * The component only renders what overview() returns; goLive() re-checks every requirement itself,
 * so a stale page or a hand-crafted request can't switch Lyro on without them.
 */
class LyroSetupService
{
    public function __construct(
        private readonly LyroAiEngine $engine,
        private readonly UsageLimiter $usage,
    ) {
    }

    /**
     * @return array{
     *     steps: array<int, array{key: string, title: string, detail: string, state: string}>,
     *     done: int, total: int, percent: int, live: bool, can_go_live: bool, syncing: int,
     *     blockers: array<int, string>,
     *     warnings: array<int, array{text: string, route: ?string, tab: ?string, link: ?string}>
     * }
     */
    public function overview(Workspace $workspace): array
    {
        $setting = AiAgentSetting::query()->where('workspace_id', $workspace->id)->first();
        $sources = $this->sourceCounts($workspace);
        $rules = $this->channelRules($setting);
        $live = (bool) $setting?->is_active;

        $steps = [
            $this->sourcesStep($sources),
            $this->playgroundStep($setting),
            $this->channelsStep($setting, $rules),
            $this->liveStep($setting, $live),
        ];

        $blockers = $this->blockers($workspace, $sources, $rules);
        $done = count(array_filter($steps, fn (array $s) => $s['state'] === 'done'));

        return [
            'steps' => $steps,
            'done' => $done,
            'total' => count($steps),
            'percent' => (int) round($done / count($steps) * 100),
            'live' => $live,
            'can_go_live' => $blockers === [],
            'syncing' => $sources['syncing'],
            'blockers' => $blockers,
            'warnings' => $this->warnings($workspace, $setting, $rules),
        ];
    }

    /** @throws RuntimeException with a human-readable reason when Lyro can't go live yet. */
    public function goLive(Workspace $workspace): void
    {
        $blockers = $this->overview($workspace)['blockers'];

        if ($blockers !== []) {
            throw new RuntimeException(implode(' ', $blockers));
        }

        DB::beginTransaction();

        try {
            $setting = AiAgentSetting::query()->where('workspace_id', $workspace->id)->lockForUpdate()->first()
                ?? new AiAgentSetting(['workspace_id' => $workspace->id]);

            $wasActive = (bool) $setting->is_active;

            $setting->is_active = true;
            if ($setting->went_live_at === null) {
                $setting->went_live_at = now();
            }
            $setting->save();

            if (! $wasActive) {
                activity('lyro')
                    ->performedOn($setting)
                    ->event('updated')
                    ->withProperties(['is_active' => true])
                    ->log('Lyro went live');
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function pause(Workspace $workspace): void
    {
        DB::beginTransaction();

        try {
            $setting = AiAgentSetting::query()->where('workspace_id', $workspace->id)->lockForUpdate()->first();

            if ($setting && $setting->is_active) {
                $setting->update(['is_active' => false]);

                activity('lyro')
                    ->performedOn($setting)
                    ->event('updated')
                    ->withProperties(['is_active' => false])
                    ->log('Lyro paused');
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /** Called after the Playground produced a real AI reply. Only the first one is recorded. */
    public function markPlaygroundTested(Workspace $workspace): void
    {
        $existing = AiAgentSetting::query()->where('workspace_id', $workspace->id)->value('playground_tested_at');

        if ($existing !== null) {
            return;
        }

        DB::beginTransaction();

        try {
            $setting = AiAgentSetting::query()->updateOrCreate(
                ['workspace_id' => $workspace->id],
                ['playground_tested_at' => now()]
            );

            activity('lyro')
                ->performedOn($setting)
                ->event('updated')
                ->withProperties(['section' => 'setup', 'step' => 'playground'])
                ->log('Lyro playground tested');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    // ------------------------------------------------------------------ steps

    /** @return array{total: int, synced: int, syncing: int, failed: int} */
    private function sourceCounts(Workspace $workspace): array
    {
        // One grouped query; toBase() keeps the status keys as plain strings.
        $byStatus = AiDataSource::query()
            ->where('workspace_id', $workspace->id)
            ->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (AiDataSourceStatus ...$statuses) => (int) collect($statuses)->sum(fn ($s) => $byStatus[$s->value] ?? 0);

        return [
            'total' => (int) $byStatus->sum(),
            'synced' => $count(AiDataSourceStatus::Synced),
            'syncing' => $count(AiDataSourceStatus::Pending, AiDataSourceStatus::Syncing),
            'failed' => $count(AiDataSourceStatus::Failed),
        ];
    }

    private function sourcesStep(array $c): array
    {
        $noun = fn (int $n) => $n.' '.Str::plural('source', $n);

        if ($c['synced'] > 0) {
            $detail = "{$c['synced']} of {$noun($c['total'])} ready";
            $detail .= $c['failed'] > 0 ? ", {$c['failed']} failed to sync." : '.';

            return ['key' => 'sources', 'title' => 'Add data sources', 'detail' => $detail, 'state' => 'done'];
        }

        if ($c['syncing'] > 0) {
            return ['key' => 'sources', 'title' => 'Add data sources', 'state' => 'working',
                'detail' => 'Reading your content… '.$noun($c['syncing']).' syncing.'];
        }

        if ($c['failed'] > 0) {
            return ['key' => 'sources', 'title' => 'Add data sources', 'state' => 'attention',
                'detail' => $noun($c['failed']).' failed to sync. Open Data sources to see why and try again.'];
        }

        return ['key' => 'sources', 'title' => 'Add data sources', 'state' => 'todo', 'detail' => 'Teach Lyro from your site and files.'];
    }

    private function playgroundStep(?AiAgentSetting $setting): array
    {
        $at = $setting?->playground_tested_at;

        return [
            'key' => 'playground',
            'title' => 'Test in the playground',
            'state' => $at ? 'done' : 'todo',
            'detail' => $at ? 'Tested '.$at->diffForHumans().'.' : 'Ask a few questions before going live.',
        ];
    }

    private function channelsStep(?AiAgentSetting $setting, array $rules): array
    {
        $reviewed = $setting !== null && $setting->channel_rules !== null;

        if ($reviewed && ! $rules['live'] && ! $rules['email']) {
            return ['key' => 'channels', 'title' => 'Choose channels', 'state' => 'attention',
                'detail' => 'Live chat and email are both off, so Lyro would answer nobody.'];
        }

        if (! $reviewed) {
            return ['key' => 'channels', 'title' => 'Choose channels', 'state' => 'todo', 'detail' => 'Turn on live chat and email.'];
        }

        $parts = array_filter([
            $rules['live'] ? 'Live chat on'.($rules['outside_hours_only'] ? ' (outside operating hours only)' : '') : null,
            $rules['email'] ? 'Email '.($rules['email_draft_only'] ? 'drafts only' : 'auto-reply') : null,
        ]);

        return ['key' => 'channels', 'title' => 'Choose channels', 'state' => 'done', 'detail' => implode(' · ', $parts).'.'];
    }

    private function liveStep(?AiAgentSetting $setting, bool $live): array
    {
        if ($live) {
            $since = $setting?->went_live_at ? ' since '.$setting->went_live_at->format('M j').'.' : '.';

            return ['key' => 'live', 'title' => 'Go live', 'state' => 'done', 'detail' => 'Lyro is answering live chat'.$since];
        }

        return ['key' => 'live', 'title' => 'Go live', 'state' => 'todo', 'detail' => 'Let Lyro answer real customers.'];
    }

    // ------------------------------------------------------------------ requirements

    /** Reasons Lyro can't be switched on yet (hard requirements). */
    private function blockers(Workspace $workspace, array $sources, array $rules): array
    {
        $blockers = [];

        if ($sources['synced'] === 0) {
            $blockers[] = 'Add at least one data source and wait for it to finish syncing.';
        }

        if (! $rules['live'] && ! $rules['email']) {
            $blockers[] = 'Turn on at least one channel.';
        }

        if (! $this->engine->isAvailable()) {
            $blockers[] = 'The AI engine is not connected. Set ANTHROPIC_API_KEY on the server.';
        }

        if ($this->usage->limit($workspace, 'ai_conversations') === 0) {
            $blockers[] = 'Your plan does not include Lyro conversations. Upgrade to enable it.';
        }

        return $blockers;
    }

    /** Things worth knowing that don't stop Lyro from going live. */
    private function warnings(Workspace $workspace, ?AiAgentSetting $setting, array $rules): array
    {
        $warnings = [];

        if ($setting?->playground_tested_at === null) {
            $warnings[] = ['text' => 'You have not tested Lyro yet. Try a few real questions first.', 'route' => null, 'tab' => 'playground', 'link' => 'Open Playground'];
        }

        if ($rules['live']) {
            $installedAt = $workspace->websites()->value('installed_at');

            if ($installedAt === null) {
                $warnings[] = ['text' => 'Live chat needs the chat widget installed on your website.', 'route' => 'app.settings.installation', 'tab' => null, 'link' => 'Install chat widget'];
            }
        }

        if ($rules['email']) {
            $warnings[] = ['text' => 'Email replies are not processed yet. Lyro currently answers live chat only.', 'route' => null, 'tab' => null, 'link' => null];
        }

        $limit = $this->usage->limit($workspace, 'ai_conversations');

        if ($limit !== null && $limit > 0 && $this->usage->used($workspace, 'ai_conversations') >= $limit) {
            $warnings[] = ['text' => 'This month\'s Lyro conversation limit is used up. New chats will not be answered until it resets or you upgrade.', 'route' => 'app.settings.billing', 'tab' => null, 'link' => 'View billing'];
        }

        return $warnings;
    }

    /** Effective Channels settings, with the same defaults the Channels tab uses when nothing is saved. */
    private function channelRules(?AiAgentSetting $setting): array
    {
        $r = $setting?->channel_rules ?? [];

        return [
            'live' => (bool) ($r['live_answer_enabled'] ?? true),
            'outside_hours_only' => (bool) ($r['live_outside_hours_only'] ?? false),
            'email' => (bool) ($r['email_answer_enabled'] ?? false),
            'email_draft_only' => (bool) ($r['email_draft_only'] ?? true),
        ];
    }
}
