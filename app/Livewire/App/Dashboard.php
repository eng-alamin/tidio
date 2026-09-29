<?php

namespace App\Livewire\App;

use Livewire\Component;

class Dashboard extends Component
{
    /** Setup checklist progress, e.g. "2/5" steps completed. */
    public int $setupDone = 2;

    public int $setupTotal = 5;

    public function skipSetup(): void
    {
        // TODO: persist a "setup_dismissed" flag on the workspace once that column/table exists.
        $this->dispatch('toast', message: 'Setup checklist hidden.');
    }

    public function render()
    {
        return view('livewire.app.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}