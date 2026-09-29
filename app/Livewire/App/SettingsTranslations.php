<?php

namespace App\Livewire\App;

use App\Models\WidgetTranslation;
use Livewire\Component;

class SettingsTranslations extends Component
{
    public string $locale = 'en';

    // The 3 widget strings shown in the static design. Extend this list (and
    // the defaults below) whenever another widget-facing string needs to be
    // translatable — nothing else needs to change, save()/mount() just loop it.
    public array $keys = [
        'header' => 'Chat with us',
        'placeholder' => 'Type a message…',
        'send_button' => 'Send',
    ];

    public array $available_locales = [
        'en' => 'English',
        'bn' => 'বাংলা',
        'de' => 'Deutsch',
    ];

    public function mount(): void
    {
        $this->loadStrings();
    }

    public function updatedLocale(): void
    {
        $this->loadStrings();
    }

    private function loadStrings(): void
    {
        $website = app('currentWorkspace')->websites()->first();

        $defaults = [
            'header' => 'Chat with us',
            'placeholder' => 'Type a message…',
            'send_button' => 'Send',
        ];

        $stored = $website
            ? WidgetTranslation::where('website_id', $website->id)
                ->where('locale', $this->locale)
                ->value('strings')
            : null;

        $this->keys = array_merge($defaults, $stored ?? []);
    }

    public function save(): void
    {
        $workspace = app('currentWorkspace');
        $website = $workspace->websites()->first();

        if (! $website) {
            $website = $workspace->websites()->create([
                'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com',
                'widget_key' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }

        WidgetTranslation::updateOrCreate(
            ['website_id' => $website->id, 'locale' => $this->locale],
            ['strings' => $this->keys]
        );

        $this->dispatch('toast', message: 'Translations saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-translations')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
