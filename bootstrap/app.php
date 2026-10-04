<?php

use App\Http\Middleware\EnforceImpersonation;
use App\Http\Middleware\EnsureBelongsToWorkspace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'workspace' => EnsureBelongsToWorkspace::class,
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
