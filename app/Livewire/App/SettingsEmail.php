<?php

namespace App\Livewire\App;

use App\Enums\ChannelType;
use App\Models\BlockedEmailAddress;
use App\Models\Channel;
use App\Models\EmailDomain;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsEmail extends Component
{
    public string $tab = 'mailbox';

    // Mailbox tab
    #[Validate('required|email')]
    public string $support_address = '';

    #[Validate('required|string|max:100')]
    public string $sender_name = 'Loop Support';

    // Sender address tab (shares sender_name above)
    #[Validate('required|email')]
    public string $sender_address = '';

    #[Validate('nullable|email')]
    public ?string $reply_to = null;

    // Domains tab
    #[Validate('required|string|max:255')]
    public string $new_domain = '';

    // Blocked addresses tab
    #[Validate('required|email')]
    public string $block_address = '';

    public function mount(): void
    {
        $channel = $this->emailChannel();

        if ($channel) {
            $creds = $channel->credentials ?? [];
            $this->support_address = $creds['support_address'] ?? '';
            $this->sender_name = $creds['sender_name'] ?? $this->sender_name;
            $this->sender_address = $creds['sender_address'] ?? '';
            $this->reply_to = $creds['reply_to'] ?? null;
        }
    }

    private function emailChannel(): ?Channel
    {
        return Channel::where('workspace_id', app('currentWorkspace')->id)
            ->where('type', ChannelType::Email)
            ->first();
    }

    #[Computed]
    public function domains(): Collection
    {
        return EmailDomain::where('workspace_id', app('currentWorkspace')->id)->latest()->get();
    }

    #[Computed]
    public function blockedAddresses(): Collection
    {
        return BlockedEmailAddress::where('workspace_id', app('currentWorkspace')->id)->latest()->get();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['mailbox', 'sender', 'blocked', 'domains'], true) ? $tab : 'mailbox';
    }

    public function connectMailbox(): void
    {
        $this->validate([
            'support_address' => 'required|email',
            'sender_name' => 'required|string|max:100',
        ]);

        Channel::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id, 'type' => ChannelType::Email],
            [
                'status' => 'connected',
                'connected_at' => now(),
                'credentials' => array_merge(
                    $this->emailChannel()?->credentials ?? [],
                    ['support_address' => $this->support_address, 'sender_name' => $this->sender_name]
                ),
            ]
        );

        $this->dispatch('toast', message: 'Mailbox connected.');
    }

    public function saveSender(): void
    {
        $this->validate([
            'sender_name' => 'required|string|max:100',
            'sender_address' => 'required|email',
            'reply_to' => 'nullable|email',
        ]);

        Channel::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id, 'type' => ChannelType::Email],
            [
                'credentials' => array_merge(
                    $this->emailChannel()?->credentials ?? [],
                    [
                        'sender_name' => $this->sender_name,
                        'sender_address' => $this->sender_address,
                        'reply_to' => $this->reply_to,
                    ]
                ),
            ]
        );

        $this->dispatch('toast', message: 'Sender address saved.');
    }

    public function addDomain(): void
    {
        $this->validate(['new_domain' => 'required|string|max:255']);

        EmailDomain::firstOrCreate([
            'workspace_id' => app('currentWorkspace')->id,
            'domain' => $this->new_domain,
        ]);

        $this->reset('new_domain');
        unset($this->domains);

        $this->dispatch('toast', message: 'Domain added — verification pending.');
    }

    public function blockAddress(): void
    {
        $this->validate(['block_address' => 'required|email']);

        BlockedEmailAddress::firstOrCreate([
            'workspace_id' => app('currentWorkspace')->id,
            'address' => $this->block_address,
        ], ['blocked_at' => now()]);

        $this->reset('block_address');
        unset($this->blockedAddresses);

        $this->dispatch('toast', message: 'Address blocked.');
    }

    public function unblock(int $id): void
    {
        BlockedEmailAddress::where('workspace_id', app('currentWorkspace')->id)->findOrFail($id)->delete();

        unset($this->blockedAddresses);

        $this->dispatch('toast', message: 'Address unblocked.');
    }

    public function render()
    {
        return view('livewire.app.settings-email')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
