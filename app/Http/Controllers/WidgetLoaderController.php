<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Response;

/**
 * Serves the tiny loader script embedded via the <script src="/widget/{key}.js">
 * snippet from Settings > Installation. Its mere being requested is what
 * flips the Dashboard's "Chat widget: Not installed" status to installed —
 * there's no separate "ping" endpoint, the script load itself is the signal.
 */
class WidgetLoaderController extends Controller
{
    public function __invoke(string $widgetKey): Response
    {
        $website = Website::where('widget_key', $widgetKey)->first();

        if ($website && ! $website->installed_at) {
            $website->update(['installed_at' => now()]);
        }

        // Placeholder loader — the real widget bundle isn't built yet.
        $js = <<<JS
        console.info('Loop chat widget loaded ({$widgetKey}). Full widget UI not implemented yet.');
        JS;

        return response($js, 200)->header('Content-Type', 'application/javascript');
    }
}
