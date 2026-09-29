<?php

namespace App\Livewire\App;

use App\Models\Plan;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsBilling extends Component
{
    #[Computed]
    public function subscription()
    {
        return app('currentWorkspace')->activeSubscription;
    }

    #[Computed]
    public function invoices()
    {
        return app('currentWorkspace')->invoices()->latest('issued_at')->limit(12)->get();
    }

    #[Computed]
    public function plans()
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function render()
    {
        return view('livewire.app.settings-billing')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
