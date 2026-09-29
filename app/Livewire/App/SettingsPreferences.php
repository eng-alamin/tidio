<?php

namespace App\Livewire\App;

use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsPreferences extends Component
{
    #[Validate('required|timezone')]
    public string $timezone = 'Asia/Dhaka';

    public string $date_format = 'M j, Y';

    public bool $sound_enabled = true;

    public function mount(): void
    {
        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];

        $this->timezone = $workspace->timezone ?? 'Asia/Dhaka';
        $this->date_format = $settings['preferences']['date_format'] ?? 'M j, Y';
        $this->sound_enabled = $settings['preferences']['sound_enabled'] ?? true;
    }

    public function save(): void
    {
        $this->validate();

        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];
        $settings['preferences']['date_format'] = $this->date_format;
        $settings['preferences']['sound_enabled'] = $this->sound_enabled;

        $workspace->update(['timezone' => $this->timezone, 'settings' => $settings]);

        $this->dispatch('toast', message: 'Preferences saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-preferences')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
