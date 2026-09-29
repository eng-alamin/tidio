<?php

namespace App\Livewire\App;

use App\Models\Webhook;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsDeveloper extends Component
{
    public string $tab = 'webhooks';

    // Webhooks tab
    public bool $showCreate = false;

    #[Validate('required|url|max:255')]
    public string $url = '';

    #[Validate('required|string|max:100')]
    public string $event = 'conversation.created';

    #[Computed]
    public function webhooks(): Collection
    {
        return Webhook::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['webhooks', 'data', 'openapi'], true) ? $tab : 'webhooks';
    }

    public function startCreate(): void
    {
        $this->reset(['url', 'event']);
        $this->showCreate = true;
    }

    public function createWebhook(): void
    {
        $this->validate();

        Webhook::create([
            'workspace_id' => app('currentWorkspace')->id,
            'url' => $this->url,
            'events' => [$this->event],
            'is_active' => true,
        ]);

        $this->reset(['url', 'event', 'showCreate']);
        unset($this->webhooks);

        $this->dispatch('toast', message: 'Webhook added.');
    }

    public function cancel(): void
    {
        $this->reset(['url', 'event', 'showCreate']);
    }

    public function toggleActive(int $webhookId): void
    {
        $webhook = Webhook::where('workspace_id', app('currentWorkspace')->id)->findOrFail($webhookId);
        $webhook->is_active = ! $webhook->is_active;
        $webhook->save();

        unset($this->webhooks);
    }

    public function deleteWebhook(int $webhookId): void
    {
        Webhook::where('workspace_id', app('currentWorkspace')->id)->where('id', $webhookId)->delete();
        unset($this->webhooks);

        $this->dispatch('toast', message: 'Webhook removed.');
    }

    public function regenerateApiKey(): void
    {
        $workspace = app('currentWorkspace');
        $workspace->update(['api_key' => 'loop_live_'.Str::random(32)]);

        $this->dispatch('toast', message: 'API key regenerated.');
    }

    public function render()
    {
        return view('livewire.app.settings-developer')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
