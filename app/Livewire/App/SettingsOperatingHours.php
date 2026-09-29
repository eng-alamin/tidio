<?php

namespace App\Livewire\App;

use App\Models\OperatingHour;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsOperatingHours extends Component
{
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public array $days = self::DAYS;

    #[Computed]
    public function hours()
    {
        $workspace = app('currentWorkspace');
        $existing = $workspace->operatingHours()->get()->keyBy('day_of_week');

        return collect(self::DAYS)->map(function ($name, $i) use ($existing, $workspace) {
            return $existing->get($i) ?? new OperatingHour([
                'workspace_id' => $workspace->id,
                'day_of_week' => $i,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => $i >= 1 && $i <= 5,
            ]);
        });
    }

    public function toggleDay(int $day): void
    {
        $workspace = app('currentWorkspace');
        $row = $workspace->operatingHours()->firstOrCreate(
            ['day_of_week' => $day],
            ['start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true]
        );
        $row->update(['is_active' => ! $row->is_active]);

        unset($this->hours);
    }

    public function updateTime(int $day, string $field, string $value): void
    {
        $workspace = app('currentWorkspace');
        $workspace->operatingHours()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'day_of_week' => $day],
            [$field => $value]
        );

        unset($this->hours);
    }

    public function render()
    {
        return view('livewire.app.settings-operating-hours')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
