<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\Widget\WidgetConfigBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves the public embed script (<script src="https://app/widget/{key}.js" async>).
 * It draws the launcher button and a hidden iframe pointing at the chat frame; everything
 * else (UI, API calls) happens inside that iframe so the host page's CSS/JS can't interfere.
 *
 * Route is public and sits behind ResolveWidgetWebsite (key lookup, suspended check).
 */
class WidgetLoaderController extends Controller
{
    public function __construct(private readonly WidgetConfigBuilder $config)
    {
    }

    public function __invoke(Request $request, string $widgetKey): Response
    {
        /** @var Website $website */
        $website = $request->attributes->get('widget_website');

        // A browser sends the embedding page as Referer: only a request from the
        // registered domain counts as "installed" (not curl, not a stranger's page).
        $website->markInstalledFrom($request->headers->get('referer'));

        return response()
            ->view('widget.loader', ['loader' => $this->config->loader($website)])
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
