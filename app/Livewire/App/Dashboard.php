<?php

namespace App\Livewire\App;

use App\Enums\ConversationStatus;
use App\Enums\FlowStatus;
use App\Models\AiUnansweredQuestion;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Channel;
use App\Models\EmailDomain;
use App\Models\Flow;
use App\Models\OnboardingProgress;
use App\Models\Order;
use App\Models\Visitor;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    // interactions | ai | sales | leads
    public string $perfTab = 'interactions';

    public function setPerfTab(string $tab): void
    {
        $this->perfTab = in_array($tab, ['interactions', 'ai', 'sales', 'leads'], true) ? $tab : 'interactions';
    }

    private function workspaceId(): int
    {
        return app('currentWorkspace')->id;
    }

    /**
     * Each step is auto-derived from real data (not a manual checkbox), and
     * self-heals into `onboarding_progress` the first time it's detected
     * complete, so the row exists for reporting even though nothing else
     * in the app writes to this table yet.
     */
    #[Computed]
    public function setupSteps(): array
    {
        $workspaceId = $this->workspaceId();
        $workspace = app('currentWorkspace');

        $steps = [
            'install_widget' => Website::where('workspace_id', $workspaceId)->whereNotNull('installed_at')->exists(),
            'connect_mailbox' => Channel::where('workspace_id', $workspaceId)->where('type', 'email')->where('status', 'connected')->exists(),
            'connect_domain' => EmailDomain::where('workspace_id', $workspaceId)->where('status', 'verified')->exists(),
            'invite_team' => $workspace->users()->count() > 1,
            'create_flow' => Flow::where('workspace_id', $workspaceId)->exists(),
        ];

        foreach ($steps as $key => $done) {
            if ($done) {
                OnboardingProgress::firstOrCreate(
                    ['workspace_id' => $workspaceId, 'step_key' => $key],
                    ['completed_at' => now()]
                );
            }
        }

        return $steps;
    }

    public function skipSetup(): void
    {
        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];
        $settings['onboarding_dismissed'] = true;
        $workspace->update(['settings' => $settings]);

        $this->dispatch('toast', message: 'Setup checklist hidden.');
    }

    #[Computed]
    public function setupDismissed(): bool
    {
        return (bool) (app('currentWorkspace')->settings['onboarding_dismissed'] ?? false);
    }

    #[Computed]
    public function quickActions(): array
    {
        $workspaceId = $this->workspaceId();

        $unassignedChats = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'chat')
            ->whereNull('assigned_operator_id')
            ->whereNotIn('status', [ConversationStatus::Solved, ConversationStatus::Spam])
            ->count();

        $unassignedTickets = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'ticket')
            ->whereNull('assigned_operator_id')
            ->whereNotIn('status', [ConversationStatus::Solved, ConversationStatus::Spam])
            ->count();

        $unansweredQuestions = AiUnansweredQuestion::where('workspace_id', $workspaceId)
            ->whereNull('resolved_at')
            ->count();

        $activeFlows = Flow::where('workspace_id', $workspaceId)
            ->where('status', FlowStatus::Active)
            ->count();

        $onlineVisitors = Visitor::where('workspace_id', $workspaceId)
            ->where('is_online', true)
            ->count();

        return compact('unassignedChats', 'unassignedTickets', 'unansweredQuestions', 'activeFlows', 'onlineVisitors');
    }

    /** Bucket a collection of timestamps into 7 daily heights (0-100) for the mini bar chart. */
    private function dailyHeights(Collection $timestamps): array
    {
        $counts = array_fill(0, 7, 0);
        $start = now()->subDays(6)->startOfDay();

        foreach ($timestamps as $ts) {
            if (! $ts) {
                continue;
            }
            $day = (int) $start->diffInDays(Carbon::parse($ts)->startOfDay(), false);
            if ($day >= 0 && $day <= 6) {
                $counts[$day]++;
            }
        }

        $max = max($counts) ?: 1;

        return array_map(fn ($c) => max(6, (int) round(($c / $max) * 100)), $counts);
    }

    #[Computed]
    public function performance(): array
    {
        $workspaceId = $this->workspaceId();
        $since = now()->subDays(6)->startOfDay();

        $conversations = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'chat')
            ->where('created_at', '>=', $since)
            ->with('aiMeta')
            ->get();

        $withAi = $conversations->filter(fn ($c) => $c->aiMeta !== null);
        $aiResolved = $withAi->filter(fn ($c) => $c->aiMeta->resolved_by_ai)->count();
        $aiRate = $withAi->count() > 0 ? round($aiResolved / $withAi->count() * 100) : 0;

        $orders = Order::where('workspace_id', $workspaceId)->where('created_at', '>=', $since)->get();
        $salesAssisted = $orders->sum('amount_cents') / 100;

        $leads = Contact::where('workspace_id', $workspaceId)->where('created_at', '>=', $since)->count();

        return [
            'interactions' => ['value' => $conversations->count(), 'bars' => $this->dailyHeights($conversations->pluck('created_at'))],
            'ai' => ['value' => $aiRate.'%', 'bars' => $this->dailyHeights($conversations->pluck('created_at'))],
            'sales' => ['value' => '$'.number_format($salesAssisted), 'bars' => $this->dailyHeights($orders->pluck('created_at'))],
            'leads' => ['value' => $leads, 'bars' => $this->dailyHeights(Contact::where('workspace_id', $workspaceId)->where('created_at', '>=', $since)->pluck('created_at'))],
        ];
    }

    #[Computed]
    public function projectStatus(): array
    {
        $workspaceId = $this->workspaceId();

        $website = Website::where('workspace_id', $workspaceId)->first();
        $mailbox = Channel::where('workspace_id', $workspaceId)->where('type', 'email')->where('status', 'connected')->exists();
        $domain = EmailDomain::where('workspace_id', $workspaceId)->where('status', 'verified')->exists();

        return [
            'widget_installed' => (bool) $website?->installed_at,
            'mailbox_connected' => $mailbox,
            'domain_connected' => $domain,
        ];
    }

    #[Computed]
    public function usage(): array
    {
        $workspace = app('currentWorkspace');
        $limits = $workspace->activeSubscription?->plan?->limits ?? ['ai_conversations' => 50];

        $conversationsUsed = $workspace->usageCounters()
            ->where('metric', 'conversations')
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->sum('count');

        $aiUsed = $workspace->usageCounters()
            ->where('metric', 'ai_conversations')
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->sum('count');

        $conversationsLimit = $limits['conversations'] ?? null;
        $aiLimit = $limits['ai_conversations'] ?? null;

        return [
            'conversations' => [
                'used' => $conversationsUsed,
                'limit' => $conversationsLimit,
                'pct' => $conversationsLimit ? min(100, round($conversationsUsed / $conversationsLimit * 100)) : 0,
            ],
            'ai_conversations' => [
                'used' => $aiUsed,
                'limit' => $aiLimit,
                'pct' => $aiLimit ? min(100, round($aiUsed / $aiLimit * 100)) : 0,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.app.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
