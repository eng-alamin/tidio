<?php

use App\Http\Middleware\BlockDuringMaintenance;
use App\Http\Middleware\EnforceImpersonation;
use App\Http\Middleware\EnsureBelongsToWorkspace;
use App\Http\Middleware\ResolveWidgetVisitor;
use App\Http\Middleware\ResolveWidgetWebsite;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Public chat-widget endpoints: no session, no cookies, no CSRF (see routes/widget.php).
            Route::middleware([])->group(base_path('routes/widget.php'));
        },
    )
    // Reverb: POST /broadcasting/auth for logged-in operators + the private channels they may join.
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware' => ['web', 'auth']])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'workspace' => EnsureBelongsToWorkspace::class,
            'maintenance' => BlockDuringMaintenance::class,
            'widget.site' => ResolveWidgetWebsite::class,
            'widget.visitor' => ResolveWidgetVisitor::class,
        ]);

        // Ends expired impersonation sessions, or ones whose staff member signed out.
        $middleware->web(append: [EnforceImpersonation::class]);

        // Unauthenticated hits on /admin/* go to the super-admin login,
        // everything else keeps the original tenant `login` route.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login')
        );

        // An already-signed-in super admin visiting /admin/login lands on the dashboard.
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->is('admin', 'admin/*')
                ? route('admin.dashboard')
                : '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
