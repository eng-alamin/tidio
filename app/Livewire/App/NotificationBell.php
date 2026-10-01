<?php

namespace App\Livewire\App;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()->notifications()->latest()->limit(10)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->where('id', $notificationId)->first();
        $notification?->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    // A new mention fires this from Inbox so the badge updates without a full poll.
    #[On('mention-created')]
    public function refresh(): void
    {
        unset($this->notifications, $this->unreadCount);
    }

    public function render()
    {
        return view('livewire.app.notification-bell');
    }
}
