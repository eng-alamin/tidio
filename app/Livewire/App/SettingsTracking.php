<?php

namespace App\Livewire\App;

use App\Models\TrackingEvent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsTracking extends Component
{
    public string $tab = 'configure';

    // Configure tab
    public bool $track_page_views = true;
    public bool $track_custom_events = false;

    // Events tab
    public bool $showCreate = false;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:150')]
    public string $trigger = '';

    #[Computed]
    public function events(): Collection
    {
        return TrackingEvent::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get();
    }

    public function mount(): void
    {
        $settings = app('currentWorkspace')->settings ?? [];

        $this->track_page_views = $settings['tracking']['page_views'] ?? true;
        $this->track_custom_events = $settings['tracking']['custom_events'] ?? false;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['configure', 'events'], true) ? $tab : 'configure';
    }

    public function saveConfig(): void
    {
        $workspace = app('currentWorkspace');
        $settings = $workspace->settings ?? [];

        $settings['tracking']['page_views'] = $this->track_page_views;
        $settings['tracking']['custom_events'] = $this->track_custom_events;

        $workspace->update(['settings' => $settings]);

        $this->dispatch('toast', message: 'Tracking configuration saved.');
    }

    public function startCreate(): void
    {
        $this->reset(['name', 'trigger']);
        $this->showCreate = true;
    }

    public function createEvent(): void
    {
        $this->validate();

        TrackingEvent::create([
            'workspace_id' => app('currentWorkspace')->id,
            'name' => $this->name,
            'trigger' => $this->trigger,
            'count_30d' => 0,
        ]);

        $this->reset(['name', 'trigger', 'showCreate']);
        unset($this->events);

        $this->dispatch('toast', message: "Event \"{$this->name}\" created.");
    }

    public function cancel(): void
    {
        $this->reset(['name', 'trigger', 'showCreate']);
    }

    public function deleteEvent(int $eventId): void
    {
        TrackingEvent::where('workspace_id', app('currentWorkspace')->id)->where('id', $eventId)->delete();
        unset($this->events);

        $this->dispatch('toast', message: 'Event deleted.');
    }

    public function render()
    {
        return view('livewire.app.settings-tracking')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
