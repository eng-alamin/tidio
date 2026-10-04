<?php

namespace App\Providers;

use App\Http\Middleware\EnsureBelongsToWorkspace;
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
        // The update route only carries `web`. Each page's own route middleware (auth, auth:super_admin,
        // workspace) is re-applied to its Livewire requests as "persistent middleware". Forcing
        // `auth` + `workspace` here made every admin-panel update depend on a tenant login.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware(['web']);
        });

        Livewire::addPersistentMiddleware([EnsureBelongsToWorkspace::class]);

        Conversation::observe(ConversationObserver::class);
    }
}
