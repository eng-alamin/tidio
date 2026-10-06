<?php

namespace App\Livewire\App;

use App\Models\Website;
use Illuminate\Support\Str;
use Livewire\Component;

class SettingsInstallation extends Component
{
    public string $widget_key = '';
    public string $snippet = '';
    public string $domain = '';
    public bool $installed = false;
    public ?string $installed_at = null;

    public function mount(): void
    {
        $website = $this->website();

        $this->widget_key = $website->widget_key;
        // Public URL (no login): served by routes/widget.php, so it works on any customer website.
        $this->snippet = '<script src="'.url('/widget/'.$this->widget_key.'.js').'" async></script>';
        $this->domain = $website->domain;

        $this->syncStatus($website);
    }

    /** Called by wire:poll while the widget hasn't been seen on the site yet. */
    public function refreshStatus(): void
    {
        $this->syncStatus($this->website());
    }

    public function saveDomain(): void
    {
        $normalized = Website::normalizeDomain($this->domain);
        $this->domain = $normalized;

        $this->validate([
            'domain' => [
                'required', 'string', 'max:190',
                'regex:/^(localhost|([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}|(\d{1,3}\.){3}\d{1,3})$/',
            ],
        ], [
            'domain.regex' => 'Enter a valid domain such as example.com (no path or spaces).',
        ]);

        $website = $this->website();

        if ($website->domain === $normalized) {
            return;
        }

        $old = $website->domain;
        $website->update(['domain' => $normalized]);

        activity('widget')
            ->causedBy(auth()->user())
            ->performedOn($website)
            ->event('updated')
            ->withProperties(['old' => ['domain' => $old], 'attributes' => ['domain' => $normalized]])
            ->log('Widget domain updated');

        $this->dispatch('toast', message: 'Domain saved. The widget will only load on '.$normalized.'.');
    }

    public function render()
    {
        return view('livewire.app.settings-installation')
            ->layout('layouts.app', ['title' => 'Settings']);
    }

    private function website(): Website
    {
        $workspace = app('currentWorkspace');
        $website = $workspace->websites()->first();

        // No Website row yet (brand-new workspace) — create one now so there's
        // a real widget_key to embed instead of showing a placeholder snippet
        // that would never actually connect to this workspace.
        if (! $website) {
            $website = $workspace->websites()->create([
                'domain' => Website::normalizeDomain((string) parse_url(config('app.url'), PHP_URL_HOST)) ?: 'example.com',
                'widget_key' => (string) Str::uuid(),
            ]);

            activity('widget')
                ->causedBy(auth()->user())
                ->performedOn($website)
                ->event('created')
                ->log('Website created for chat widget');
        }

        return $website;
    }

    private function syncStatus(Website $website): void
    {
        $this->installed = $website->installed_at !== null;
        $this->installed_at = $website->installed_at?->diffForHumans();
    }
}
