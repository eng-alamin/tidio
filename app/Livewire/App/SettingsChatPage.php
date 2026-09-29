<?php

namespace App\Livewire\App;

use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsChatPage extends Component
{
    public bool $enabled = true;

    #[Validate('required|string|max:60')]
    public string $page_title = 'Chat with Loop';

    public string $page_link = '';

    public function mount(): void
    {
        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];

        $this->enabled = $settings['chat_page']['enabled'] ?? true;
        $this->page_title = $settings['chat_page']['title'] ?? ('Chat with '.$workspace->name);
        // Derived from the workspace slug, not stored — changing the workspace
        // name/slug should move the link automatically rather than leaving a
        // stale copy sitting in the settings JSON.
        $this->page_link = url('/chat/'.$workspace->slug);
    }

    public function save(): void
    {
        $this->validate();

        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];

        $settings['chat_page']['enabled'] = $this->enabled;
        $settings['chat_page']['title'] = $this->page_title;

        $workspace->update(['settings' => $settings]);

        $this->dispatch('toast', message: 'Chat page settings saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-chat-page')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
