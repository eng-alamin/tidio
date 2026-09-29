<?php

namespace App\Livewire\App;

use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsNotifications extends Component
{
    public const EVENTS = [
        'new_conversation' => 'New conversation assigned to you',
        'new_message' => 'New message in your conversation',
        'mention' => 'Someone mentions you',
        'flow_completed' => 'A flow finishes running',
    ];

    #[Computed]
    public function prefs()
    {
        $workspace = app('currentWorkspace');
        $userId = auth()->id();

        $existing = NotificationPreference::where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->get()
            ->keyBy(fn ($p) => $p->event_type.'-'.$p->channel->value);

        $grid = [];
        foreach (self::EVENTS as $event => $label) {
            foreach (NotificationChannel::cases() as $channel) {
                $grid[$event][$channel->value] = $existing->get($event.'-'.$channel->value)?->is_enabled
                    ?? ($channel === NotificationChannel::Email);
            }
        }

        return $grid;
    }

    public function toggle(string $event, string $channel): void
    {
        $workspace = app('currentWorkspace');

        $pref = NotificationPreference::firstOrNew([
            'workspace_id' => $workspace->id,
            'user_id' => auth()->id(),
            'event_type' => $event,
            'channel' => $channel,
        ]);

        $pref->is_enabled = ! ($pref->exists ? $pref->is_enabled : ($channel === NotificationChannel::Email->value));
        $pref->save();

        unset($this->prefs);
    }

    public function render()
    {
        return view('livewire.app.settings-notifications')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
