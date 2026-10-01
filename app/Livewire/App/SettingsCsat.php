<?php

namespace App\Livewire\App;

use App\Enums\CsatTrigger;
use App\Models\CsatSetting;
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
        $settings = app('currentWorkspace')->csatSetting;

        $this->ask_rating = $settings?->is_enabled ?? true;
        $this->ask_comment = filled($settings?->follow_up_question);
        $this->ticket_rating_email = $settings?->ticket_rating_email ?? true;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['conversations', 'ticketing'], true) ? $tab : 'conversations';
    }

    public function save(): void
    {
        CsatSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            [
                'is_enabled' => $this->ask_rating,
                'trigger' => CsatTrigger::AfterResolution,
                'follow_up_question' => $this->ask_comment ? 'Anything else you\'d like to share?' : null,
                'ticket_rating_email' => $this->ticket_rating_email,
            ]
        );

        $this->dispatch('toast', message: 'Customer satisfaction settings saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-csat')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
