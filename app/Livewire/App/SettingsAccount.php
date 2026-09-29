<?php

namespace App\Livewire\App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsAccount extends Component
{
    public string $tab = 'details';

    // Details tab
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $language = 'English';

    // Signature tab
    public string $signature = '';

    // Password tab
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->signature = $user->signature ?? '';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['details', 'signature', 'password'], true) ? $tab : 'details';
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user = Auth::user();
        $user->update(['name' => $this->name, 'email' => $this->email]);

        $this->dispatch('toast', message: 'Account details saved.');
    }

    public function saveSignature(): void
    {
        $this->validate([
            'signature' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();
        $user->update(['signature' => $this->signature]);

        $this->dispatch('toast', message: 'Signature saved.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('toast', message: 'Password changed.');
    }

    public function render()
    {
        return view('livewire.app.settings-account')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}