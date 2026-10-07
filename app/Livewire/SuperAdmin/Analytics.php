<?php

namespace App\Livewire\SuperAdmin;

use App\Services\SuperAdmin\AnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.super-admin')]
#[Title('Analytics')]
class Analytics extends Component
{
    /** @return list<array{label:string, count:int, height:int}> */
    #[Computed]
    public function signups(): array
    {
        return $this->withHeights(app(AnalyticsService::class)->weeklySignups());
    }

    /** @return list<array{label:string, count:int, height:int}> */
    #[Computed]
    public function tickets(): array
    {
        return $this->withHeights(app(AnalyticsService::class)->weeklyTickets());
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function kpis(): array
    {
        return app(AnalyticsService::class)->kpis();
    }

    /**
     * Adds a bar height (percent of the tallest bar, never below 4 so an empty week stays visible).
     *
     * @param  list<array{label:string, count:int}>  $weeks
     * @return list<array{label:string, count:int, height:int}>
     */
    private function withHeights(array $weeks): array
    {
        $max = max(1, ...array_column($weeks, 'count'));

        return array_map(
            fn (array $week) => $week + ['height' => max(4, (int) round($week['count'] / $max * 100))],
            $weeks
        );
    }

    public function render()
    {
        return view('livewire.super-admin.analytics');
    }
}
