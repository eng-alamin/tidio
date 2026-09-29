<?php

namespace App\Livewire\App;

use Livewire\Component;

class SettingsDownloadApp extends Component
{
    public function render()
    {
        return view('livewire.app.settings-download-app')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
