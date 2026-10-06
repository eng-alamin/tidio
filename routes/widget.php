<?php

use App\Http\Controllers\Widget\WidgetFrameController;
use App\Http\Controllers\Widget\WidgetMessageController;
use App\Http\Controllers\Widget\WidgetSessionController;
use App\Http\Controllers\WidgetLoaderController;
use Illuminate\Support\Facades\Route;

/*
| Public, cookie-less, CSRF-free endpoints used by the embeddable chat widget.
| Loaded from bootstrap/app.php WITHOUT the `web` middleware group on purpose:
| visitors are identified by an explicit X-Widget-Session header, never by cookies.
*/

// 1. The one-line embed script.
Route::get('/widget/{widgetKey}.js', WidgetLoaderController::class)
    ->middleware(['throttle:widget-asset', 'widget.site'])
    ->name('widget.loader');

// 2. The chat window (embedded as an iframe by the script above).
Route::get('/widget-frame/{widgetKey}', WidgetFrameController::class)
    ->middleware(['throttle:widget-asset', 'widget.site'])
    ->name('widget.frame');

// 3. JSON API used by the chat window.
Route::prefix('widget-api/{widgetKey}')
    ->middleware('widget.site')
    ->name('widget.api.')
    ->group(function () {
        Route::post('init', [WidgetSessionController::class, 'init'])
            ->middleware('throttle:widget-init')
            ->name('init');

        Route::middleware('widget.visitor')->group(function () {
            Route::get('messages', [WidgetMessageController::class, 'index'])
                ->middleware('throttle:widget-poll')
                ->name('messages.index');

            Route::post('messages', [WidgetMessageController::class, 'store'])
                ->middleware('throttle:widget-send')
                ->name('messages.store');

            Route::post('identify', [WidgetSessionController::class, 'identify'])
                ->middleware('throttle:widget-identify')
                ->name('identify');
        });
    });
