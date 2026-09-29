<?php

namespace App\Livewire\App;

use App\Models\Channel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsSocialChannel extends Component
{
    public string $type;

    public bool $showConnect = false;

    #[Validate('required|string|max:255')]
    public string $handle = '';

    public function mount(string $type): void
    {
        $this->type = $type;
    }

    #[Computed]
    public function channel(): ?Channel
    {
        return Channel::where('workspace_id', app('currentWorkspace')->id)
            ->where('type', $this->type)
            ->first();
    }

    public function connect(): void
    {
        $this->validate();

        Channel::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id, 'type' => $this->type],
            ['status' => 'connected', 'connected_at' => now(), 'credentials' => ['handle' => $this->handle]]
        );

        $this->reset(['handle', 'showConnect']);
        unset($this->channel);

        $this->dispatch('toast', message: ucfirst($this->type).' connected.');
    }

    public function disconnect(): void
    {
        $this->channel?->update(['status' => 'disconnected']);
        unset($this->channel);

        $this->dispatch('toast', message: ucfirst($this->type).' disconnected.');
    }

    public function render()
    {
        return view('livewire.app.settings-social-channel')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
