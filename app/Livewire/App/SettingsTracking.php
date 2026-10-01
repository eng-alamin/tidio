<?php

namespace App\Livewire\App;

use App\Enums\TrackingProvider;
use App\Models\TrackingEvent;
use App\Models\TrackingSetting;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsTracking extends Component
{
    public string $tab = 'configure';

    // Configure tab — real CRUD against TrackingSetting (one row per
    // provider). Previously this piggybacked on workspace.settings JSON
    // with two generic toggles that matched the static mockup but not the
    // real tracking_settings table (provider/snippet_id/custom_script) —
    // same class of disconnect SettingsCsat had before it was fixed.
    public bool $showProviderForm = false;

    public ?int $editingId = null;

    #[Validate('required|string')]
    public string $provider = 'google_analytics';

    #[Validate('nullable|string|max:255')]
    public string $snippet_id = '';

    #[Validate('nullable|string|max:5000')]
    public string $custom_script = '';

    // Events tab
    public bool $showCreate = false;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:150')]
    public string $trigger = '';

    #[Computed]
    public function providers(): Collection
    {
        return TrackingSetting::where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get();
    }

    #[Computed]
    public function events(): Collection
    {
        return TrackingEvent::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['configure', 'events'], true) ? $tab : 'configure';
    }

    // ---- Configure (tracking providers) ----

    public function startAddProvider(): void
    {
        $this->reset(['provider', 'snippet_id', 'custom_script', 'editingId']);
        $this->provider = 'google_analytics';
        $this->showProviderForm = true;
        $this->resetValidation();
    }

    public function editProvider(int $id): void
    {
        $setting = TrackingSetting::where('workspace_id', app('currentWorkspace')->id)->findOrFail($id);

        $this->editingId = $setting->id;
        $this->provider = $setting->provider->value;
        $this->snippet_id = $setting->snippet_id ?? '';
        $this->custom_script = $setting->custom_script ?? '';
        $this->showProviderForm = true;
        $this->resetValidation();
    }

    public function cancelProvider(): void
    {
        $this->showProviderForm = false;
    }

    public function saveProvider(): void
    {
        $isCustom = $this->provider === 'custom';

        $this->validate([
            'provider' => 'required|string',
            'snippet_id' => $isCustom ? 'nullable|string|max:255' : 'required|string|max:255',
            'custom_script' => $isCustom ? 'required|string|max:5000' : 'nullable|string|max:5000',
        ]);

        $data = [
            'provider' => TrackingProvider::from($this->provider),
            'snippet_id' => $isCustom ? null : $this->snippet_id,
            'custom_script' => $isCustom ? $this->custom_script : null,
        ];

        if ($this->editingId) {
            TrackingSetting::where('workspace_id', app('currentWorkspace')->id)
                ->where('id', $this->editingId)
                ->update($data);
            $toast = 'Tracking provider updated.';
        } else {
            // One row per provider per workspace — adding the same provider
            // again edits the existing connection instead of duplicating it.
            TrackingSetting::updateOrCreate(
                ['workspace_id' => app('currentWorkspace')->id, 'provider' => $data['provider']],
                $data + ['is_active' => true]
            );
            $toast = 'Tracking provider connected.';
        }

        $this->reset(['provider', 'snippet_id', 'custom_script', 'editingId', 'showProviderForm']);
        unset($this->providers);

        $this->dispatch('toast', message: $toast);
    }

    public function toggleProvider(int $id): void
    {
        $setting = TrackingSetting::where('workspace_id', app('currentWorkspace')->id)->findOrFail($id);
        $setting->is_active = ! $setting->is_active;
        $setting->save();

        unset($this->providers);
    }

    public function deleteProvider(int $id): void
    {
        TrackingSetting::where('workspace_id', app('currentWorkspace')->id)->where('id', $id)->delete();
        unset($this->providers);

        $this->dispatch('toast', message: 'Tracking provider removed.');
    }

    // ---- Events (unchanged — already backed by the real TrackingEvent table) ----

    public function startCreate(): void
    {
        $this->reset(['name', 'trigger']);
        $this->showCreate = true;
    }

    public function createEvent(): void
    {
        $this->validate(['name' => 'required|string|max:100', 'trigger' => 'required|string|max:150']);

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
