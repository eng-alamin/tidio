<?php

namespace App\Livewire\App;

use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsUsage extends Component
{
    #[Computed]
    public function metrics(): array
    {
        $workspace = app('currentWorkspace');
        $limits = $workspace->activeSubscription?->plan?->limits ?? ['operators' => 5, 'ai_conversations' => 50];

        $aiUsed = $workspace->usageCounters()
            ->where('metric', 'ai_conversations')
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->sum('count');

        $operatorsUsed = $workspace->users()->wherePivot('status', 'active')->count();

        return [
            [
                'label' => 'AI conversations',
                'used' => $aiUsed,
                'limit' => $limits['ai_conversations'] ?? null,
            ],
            [
                'label' => 'Operators',
                'used' => $operatorsUsed,
                'limit' => $limits['operators'] ?? null,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.app.settings-usage')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
