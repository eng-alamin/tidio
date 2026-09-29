<?php

namespace App\Livewire\App;

use App\Models\AiDataSource;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\CsatRating;
use App\Models\Message;
use App\Models\Order;
use App\Models\OperatorSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Analytics extends Component
{
    // overview | sales | leads | ai | human
    public string $tab = 'overview';

    // ai sub: live | emails | knowledge
    public string $aiSub = 'live';

    // human sub: live | tickets | operators | hours
    public string $humanSub = 'live';

    public string $from;

    public string $to;

    public function mount(): void
    {
        $this->to = now()->toDateString();
        $this->from = now()->subDays(29)->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['overview', 'sales', 'leads', 'ai', 'human'], true) ? $tab : 'overview';
    }

    public function setAiSub(string $sub): void
    {
        $this->aiSub = in_array($sub, ['live', 'emails', 'knowledge'], true) ? $sub : 'live';
    }

    public function setHumanSub(string $sub): void
    {
        $this->humanSub = in_array($sub, ['live', 'tickets', 'operators', 'hours'], true) ? $sub : 'live';
    }

    private function range(): array
    {
        return [Carbon::parse($this->from)->startOfDay(), Carbon::parse($this->to)->endOfDay()];
    }

    private function workspaceId(): int
    {
        return app('currentWorkspace')->id;
    }

    /** Bucket a collection of Carbon-ish timestamps into 7 roughly-equal segments across the range, as 0-100 heights. */
    private function weeklyHeights(Collection $timestamps): array
    {
        [$from, $to] = $this->range();
        $spanSeconds = max($to->diffInSeconds($from), 1);
        $bucketSeconds = $spanSeconds / 7;

        $counts = array_fill(0, 7, 0);

        foreach ($timestamps as $ts) {
            if (! $ts) {
                continue;
            }
            $offset = Carbon::parse($ts)->diffInSeconds($from, false);
            $bucket = (int) floor($offset / $bucketSeconds);
            $bucket = max(0, min(6, $bucket));
            $counts[$bucket]++;
        }

        $max = max($counts) ?: 1;

        return array_map(fn ($c) => max(8, (int) round(($c / $max) * 100)), $counts);
    }

    private function formatSeconds(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $seconds = (int) round($seconds);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        if ($minutes < 60) {
            return $rest > 0 ? "{$minutes}m {$rest}s" : "{$minutes}m";
        }

        $hours = intdiv($minutes, 60);
        $restMinutes = $minutes % 60;

        return "{$hours}h {$restMinutes}m";
    }

    // ===================== OVERVIEW =====================

    #[Computed]
    public function overview(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $conversations = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'chat')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $withAi = Conversation::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('aiMeta')
            ->with('aiMeta')
            ->get();

        $aiResolutionRate = $withAi->isNotEmpty()
            ? round($withAi->filter(fn ($c) => $c->aiMeta->resolved_by_ai)->count() / $withAi->count() * 100)
            : 0;

        $avgFirstResponse = Conversation::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('metric', fn ($q) => $q->whereNotNull('first_response_seconds'))
            ->with('metric')
            ->get()
            ->avg(fn ($c) => $c->metric->first_response_seconds);

        $satisfaction = CsatRating::whereHas('conversation', fn ($q) => $q
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to]))
            ->avg('rating');

        return [
            'live_conversations' => $conversations->count(),
            'ai_resolution_rate' => $aiResolutionRate.'%',
            'avg_first_response' => $this->formatSeconds($avgFirstResponse),
            'satisfaction' => $satisfaction !== null ? round($satisfaction / 5 * 100).'%' : '—',
            'bars' => $this->weeklyHeights($conversations->pluck('created_at')),
        ];
    }

    // ===================== SALES =====================

    #[Computed]
    public function sales(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $orders = Order::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $conversationCount = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'chat')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $revenue = $orders->sum('amount_cents') / 100;
        $orderCount = $orders->count();

        $byProduct = $orders->groupBy('product_name')->map(fn ($rows, $name) => [
            'name' => $name,
            'orders' => $rows->count(),
            'revenue' => $rows->sum('amount_cents') / 100,
        ])->sortByDesc('revenue')->values();

        return [
            'sales_assisted' => '$'.number_format($revenue),
            'orders' => $orderCount,
            'conversion_rate' => $conversationCount > 0 ? round($orderCount / $conversationCount * 100, 1).'%' : '0%',
            'avg_order_value' => $orderCount > 0 ? '$'.number_format($revenue / $orderCount) : '$0',
            'bars' => $this->weeklyHeights($orders->pluck('created_at')),
            'by_product' => $byProduct,
        ];
    }

    // ===================== LEADS =====================

    #[Computed]
    public function leads(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $contacts = Contact::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $chatSources = ['widget', 'messenger', 'instagram', 'whatsapp'];
        $viaChat = $contacts->whereIn('source', $chatSources)->count();

        $viaFlows = Contact::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('flowRuns')
            ->count();

        // "Qualified" = has at least one tag applied (a proxy for CRM qualification,
        // since there's no dedicated lead-scoring field on Contact).
        $qualified = $contacts->filter(fn ($c) => $c->tags()->exists())->count();

        $bySource = $contacts->groupBy(fn ($c) => $c->source ?? 'unknown')
            ->map(function ($rows, $source) {
                return [
                    'source' => ucfirst($source),
                    'leads' => $rows->count(),
                    'qualified' => $rows->filter(fn ($c) => $c->tags()->exists())->count(),
                ];
            })->sortByDesc('leads')->values();

        return [
            'leads_acquired' => $contacts->count(),
            'via_chat' => $viaChat,
            'via_flows' => $viaFlows,
            'qualified' => $qualified,
            'bars' => $this->weeklyHeights($contacts->pluck('created_at')),
            'by_source' => $bySource,
        ];
    }

    // ===================== AI SUPPORT: LIVE =====================

    #[Computed]
    public function aiLive(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $withAi = Conversation::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('aiMeta')
            ->with(['aiMeta', 'metric'])
            ->get();

        $resolved = $withAi->filter(fn ($c) => $c->aiMeta->resolved_by_ai)->count();
        $handedOff = $withAi->filter(fn ($c) => $c->aiMeta->handed_off_at !== null)->count();

        // No dedicated AI-answer-latency field exists — first_response_seconds
        // on ai-touched conversations is used as an approximation.
        $avgAnswer = $withAi->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->first_response_seconds);

        return [
            'ai_conversations' => $withAi->count(),
            'resolved_pct' => $withAi->isNotEmpty() ? round($resolved / $withAi->count() * 100).'%' : '0%',
            'handed_off_pct' => $withAi->isNotEmpty() ? round($handedOff / $withAi->count() * 100).'%' : '0%',
            'avg_answer_time' => $this->formatSeconds($avgAnswer),
            'bars' => $this->weeklyHeights($withAi->pluck('created_at')),
        ];
    }

    // ===================== AI SUPPORT: EMAILS =====================

    #[Computed]
    public function aiEmails(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $emailConvos = Conversation::where('workspace_id', $workspaceId)
            ->where('channel_type', 'email')
            ->whereBetween('created_at', [$from, $to])
            ->with(['aiMeta', 'metric'])
            ->get();

        $resolvedByAi = $emailConvos->filter(fn ($c) => $c->aiMeta?->resolved_by_ai)->count();

        // "Drafts sent" has no dedicated tracking field — approximated as
        // operator-sent replies inside AI-touched email conversations.
        $draftsSent = Message::whereIn('conversation_id', $emailConvos->pluck('id'))
            ->where('sender_type', 'operator')
            ->count();

        $avgReply = $emailConvos->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->first_response_seconds);

        return [
            'emails_handled' => $emailConvos->count(),
            'resolved_pct' => $emailConvos->isNotEmpty() ? round($resolvedByAi / $emailConvos->count() * 100).'%' : '0%',
            'drafts_sent' => $draftsSent,
            'avg_reply_time' => $this->formatSeconds($avgReply),
            'bars' => $this->weeklyHeights($emailConvos->pluck('created_at')),
        ];
    }

    // ===================== AI SUPPORT: KNOWLEDGE =====================

    #[Computed]
    public function aiKnowledge(): array
    {
        $workspaceId = $this->workspaceId();

        // hits_count/success_count are lifetime counters (no per-hit timestamp
        // log exists), so this sub-tab isn't affected by the date range.
        $sources = AiDataSource::where('workspace_id', $workspaceId)->get();

        $answersGiven = $sources->sum('hits_count');
        $topRate = $sources->map(fn ($s) => $s->successRate())->filter()->max();

        [$from, $to] = $this->range();

        // Proxy for "unanswered": AI touched the conversation but neither
        // resolved it nor handed it to a human.
        $unanswered = Conversation::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('aiMeta', fn ($q) => $q->where('resolved_by_ai', false)->whereNull('handed_off_at'))
            ->count();

        return [
            'sources' => $sources->count(),
            'answers_given' => number_format($answersGiven),
            'unanswered' => $unanswered,
            'top_source_hit' => $topRate !== null ? round($topRate).'%' : '—',
            'by_source' => $sources->sortByDesc('hits_count')->values()->map(fn ($s) => [
                'name' => $s->source,
                'used' => $s->hits_count,
                'success_rate' => $s->successRate() !== null ? round($s->successRate()).'%' : '—',
            ]),
        ];
    }

    // ===================== HUMAN SUPPORT: LIVE =====================

    #[Computed]
    public function humanLive(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $conversations = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'chat')
            ->whereBetween('created_at', [$from, $to])
            ->with('metric')
            ->get();

        $avgFirstResponse = $conversations->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->first_response_seconds);
        $avgResolution = $conversations->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->resolution_seconds);

        $satisfaction = CsatRating::whereHas('conversation', fn ($q) => $q
            ->where('workspace_id', $workspaceId)
            ->where('type', 'chat')
            ->whereBetween('created_at', [$from, $to]))
            ->avg('rating');

        return [
            'conversations' => $conversations->count(),
            'avg_first_response' => $this->formatSeconds($avgFirstResponse),
            'avg_resolution' => $this->formatSeconds($avgResolution),
            'satisfaction' => $satisfaction !== null ? round($satisfaction / 5 * 100).'%' : '—',
            'bars' => $this->weeklyHeights($conversations->pluck('created_at')),
        ];
    }

    // ===================== HUMAN SUPPORT: TICKETS =====================

    #[Computed]
    public function humanTickets(): array
    {
        [$from, $to] = $this->range();
        $workspaceId = $this->workspaceId();

        $tickets = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'ticket')
            ->whereBetween('created_at', [$from, $to])
            ->with('metric')
            ->get();

        $solved = $tickets->where('status', \App\Enums\ConversationStatus::Solved)->count();
        $open = $tickets->whereIn('status', [\App\Enums\ConversationStatus::Open, \App\Enums\ConversationStatus::Pending])->count();

        $avgResolution = $tickets->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->resolution_seconds);

        return [
            'new_tickets' => $tickets->count(),
            'solved' => $solved,
            'open' => $open,
            'avg_resolution' => $this->formatSeconds($avgResolution),
            'bars' => $this->weeklyHeights($tickets->pluck('created_at')),
        ];
    }

    // ===================== HUMAN SUPPORT: OPERATORS =====================

    #[Computed]
    public function humanOperators(): array
    {
        [$from, $to] = $this->range();
        $workspace = app('currentWorkspace');
        $workspaceId = $workspace->id;

        $operators = $workspace->users()->wherePivot('status', 'active')->get();

        $rows = $operators->map(function ($operator) use ($workspaceId, $from, $to) {
            $convos = Conversation::where('workspace_id', $workspaceId)
                ->where('assigned_operator_id', $operator->id)
                ->whereBetween('created_at', [$from, $to])
                ->with('metric')
                ->get();

            $avgResponse = $convos->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->first_response_seconds);

            $csat = CsatRating::whereHas('conversation', fn ($q) => $q
                ->where('workspace_id', $workspaceId)
                ->where('assigned_operator_id', $operator->id)
                ->whereBetween('created_at', [$from, $to]))
                ->avg('rating');

            return [
                'name' => $operator->name,
                'conversations' => $convos->count(),
                'avg_response' => $this->formatSeconds($avgResponse),
                'csat' => $csat !== null ? round($csat / 5 * 100).'%' : '—',
                '_sort' => $convos->count(),
            ];
        })->sortByDesc('_sort')->values();

        $allConvos = Conversation::where('workspace_id', $workspaceId)
            ->whereNotNull('assigned_operator_id')
            ->whereBetween('created_at', [$from, $to])
            ->with('metric')
            ->get();

        $avgFirstResponse = $allConvos->filter(fn ($c) => $c->metric)->avg(fn ($c) => $c->metric->first_response_seconds);

        $satisfaction = CsatRating::whereHas('conversation', fn ($q) => $q
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to]))
            ->avg('rating');

        return [
            'active_operators' => $operators->count(),
            'top_responder' => $rows->first()['name'] ?? '—',
            'avg_first_response' => $this->formatSeconds($avgFirstResponse),
            'satisfaction' => $satisfaction !== null ? round($satisfaction / 5 * 100).'%' : '—',
            'bars' => $this->weeklyHeights($allConvos->pluck('created_at')),
            'rows' => $rows,
        ];
    }

    // ===================== HUMAN SUPPORT: ONLINE HOURS =====================

    #[Computed]
    public function humanHours(): array
    {
        [$from, $to] = $this->range();
        $workspace = app('currentWorkspace');
        $workspaceId = $workspace->id;

        $sessions = OperatorSession::where('workspace_id', $workspaceId)
            ->where('started_at', '<=', $to)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $from))
            ->get();

        $totalSeconds = $sessions->sum(fn ($s) => $s->durationInSeconds());
        $operatorCount = max($workspace->users()->wherePivot('status', 'active')->count(), 1);

        $byDay = $sessions->groupBy(fn ($s) => $s->started_at->format('l'))
            ->map(fn ($rows) => $rows->sum(fn ($s) => $s->durationInSeconds()));
        $busiestDay = $byDay->sortDesc()->keys()->first() ?? '—';

        $daysInRange = max($from->diffInDays($to) + 1, 1);
        $daysCovered = $sessions->map(fn ($s) => $s->started_at->toDateString())->unique()->count();
        $coverage = round(min($daysCovered / $daysInRange, 1) * 100);

        return [
            'total_online' => round($totalSeconds / 3600).'h',
            'avg_per_operator' => round(($totalSeconds / 3600) / $operatorCount).'h',
            'busiest_day' => $busiestDay,
            'coverage' => $coverage.'%',
            'bars' => $this->weeklyHeights($sessions->pluck('started_at')),
        ];
    }

    public function render()
    {
        return view('livewire.app.analytics')
            ->layout('layouts.app', ['title' => 'Analytics']);
    }
}
