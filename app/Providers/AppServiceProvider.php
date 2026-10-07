<?php

namespace App\Providers;

use App\Http\Middleware\BlockDuringMaintenance;
use App\Http\Middleware\EnsureBelongsToWorkspace;
use App\Models\Conversation;
use App\Observers\ConversationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        Livewire::addPersistentMiddleware([EnsureBelongsToWorkspace::class, BlockDuringMaintenance::class]);

        Conversation::observe(ConversationObserver::class);

        $this->registerWidgetRateLimiters();
    }

    /**
     * Public chat-widget limits (requests / minute), always keyed by widget key so one noisy site
     * can't starve another. Endpoints that run after the visitor was authenticated are keyed by
     * session; asset and init are keyed by IP only, because a client could otherwise rotate a fake
     * X-Widget-Session header to dodge the limit while minting new visitors.
     */
    private function registerWidgetRateLimiters(): void
    {
        foreach (['asset', 'init', 'poll', 'send', 'identify', 'auth'] as $name) {
            RateLimiter::for("widget-{$name}", function (Request $request) use ($name) {
                $who = in_array($name, ['asset', 'init'], true)
                    ? $request->ip()
                    : ($request->header('X-Widget-Session') ?: $request->ip());

                return Limit::perMinute((int) config("widget.rate_limits.{$name}", 60))
                    ->by($name.'|'.$request->route('widgetKey').'|'.$who);
            });
        }
    }
}
