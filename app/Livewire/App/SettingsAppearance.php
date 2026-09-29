<?php

namespace App\Livewire\App;

use App\Models\WidgetSetting;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsAppearance extends Component
{
    #[Validate('required|string|max:60')]
    public string $header = 'Chat with us';

    #[Validate('required|string|max:255')]
    public string $welcome_message = 'Hi! How can we help you today?';

    #[Validate('required|string')]
    public string $background_color = '#2B5FE2';

    #[Validate('required|in:right,left')]
    public string $position = 'right';

    // The 4 preset swatches shown in the static design — kept as a small fixed
    // palette rather than a free color picker, matching settings-appearance.html.
    public array $swatches = ['#2B5FE2', '#14213D', '#1FAA59', '#FFC93C'];

    public function mount(): void
    {
        $website = app('currentWorkspace')->websites()->first();

        if ($website?->widgetSetting) {
            $s = $website->widgetSetting;
            $this->header = $s->header ?? $this->header;
            $this->welcome_message = $s->welcome_message ?? $this->welcome_message;
            $this->background_color = $s->background_color ?? $this->background_color;
            $this->position = $s->position ?? $this->position;
        }
    }

    public function save(): void
    {
        $this->validate();

        $workspace = app('currentWorkspace');
        $website = $workspace->websites()->first();

        // A brand-new workspace has no Website row yet (that's normally created
        // during onboarding) — create a bare one here so Appearance is usable
        // standalone instead of dead-ending with "no website found".
        if (! $website) {
            $website = $workspace->websites()->create([
                'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com',
                'widget_key' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }

        WidgetSetting::updateOrCreate(
            ['website_id' => $website->id],
            [
                'header' => $this->header,
                'welcome_message' => $this->welcome_message,
                'background_color' => $this->background_color,
                'position' => $this->position,
            ]
        );

        $this->dispatch('toast', message: 'Widget appearance saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-appearance')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
