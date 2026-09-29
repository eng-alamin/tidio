<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Observers\ConversationObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', 'auth', 'workspace']);
        });

        Conversation::observe(ConversationObserver::class);
    }
}
