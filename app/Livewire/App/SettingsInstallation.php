<?php

namespace App\Livewire\App;

use Illuminate\Support\Str;
use Livewire\Component;

class SettingsInstallation extends Component
{
    public string $widget_key = '';
    public string $snippet = '';

    public function mount(): void
    {
        $workspace = app('currentWorkspace');
        $website = $workspace->websites()->first();

        // No Website row yet (brand-new workspace) — create one now so there's
        // a real widget_key to embed instead of showing a placeholder snippet
        // that would never actually connect to this workspace.
        if (! $website) {
            $website = $workspace->websites()->create([
                'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com',
                'widget_key' => (string) Str::uuid(),
            ]);
        }

        $this->widget_key = $website->widget_key;
        $this->snippet = '<script src="'.url('/widget/'.$this->widget_key.'.js').'" async></script>';
    }

    public function render()
    {
        return view('livewire.app.settings-installation')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
