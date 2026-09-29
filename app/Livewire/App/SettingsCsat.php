<?php

namespace App\Livewire\App;

use Livewire\Component;

class SettingsCsat extends Component
{
    public string $tab = 'conversations';

    // Conversations tab
    public bool $ask_rating = true;
    public bool $ask_comment = false;

    // Ticketing tab
    public bool $ticket_rating_email = true;

    public function mount(): void
    {
        $settings = app('currentWorkspace')->settings ?? [];

        $this->ask_rating = $settings['csat']['conversations']['ask_rating'] ?? true;
        $this->ask_comment = $settings['csat']['conversations']['ask_comment'] ?? false;
        $this->ticket_rating_email = $settings['csat']['ticketing']['rating_email'] ?? true;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['conversations', 'ticketing'], true) ? $tab : 'conversations';
    }

    public function save(): void
    {
        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];

        $settings['csat']['conversations']['ask_rating'] = $this->ask_rating;
        $settings['csat']['conversations']['ask_comment'] = $this->ask_comment;
        $settings['csat']['ticketing']['rating_email'] = $this->ticket_rating_email;

        $workspace->update(['settings' => $settings]);

        $this->dispatch('toast', message: 'Customer satisfaction settings saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-csat')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
