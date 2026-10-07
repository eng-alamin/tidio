<?php

namespace App\Http\Controllers\Widget;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\Realtime\RealtimeConfig;
use App\Services\Widget\WidgetConfigBuilder;
use App\Services\Widget\WidgetOriginPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** The chat window that the loader embeds as an iframe. */
class WidgetFrameController extends Controller
{
    public function __construct(
        private readonly WidgetConfigBuilder $config,
        private readonly WidgetOriginPolicy $origins,
        private readonly RealtimeConfig $realtime,
    ) {
    }

    public function __invoke(Request $request, string $widgetKey): Response
    {
        /** @var Website $website */
        $website = $request->attributes->get('widget_website');

        $nonce = Str::random(24);

        // frame-ancestors is what actually stops other sites from embedding this chat;
        // the rest locks the frame down to its own inline script/style and same-origin API calls.
        // The only extra thing the frame may talk to is the Reverb socket, when real-time is on.
        $connect = trim("'self' ".$this->realtime->connectSrc());

        $csp = implode('; ', [
            "default-src 'none'",
            "script-src 'nonce-{$nonce}'",
            "style-src 'nonce-{$nonce}'",
            "connect-src {$connect}",
            "img-src 'self' data:",
            "base-uri 'none'",
            "form-action 'none'",
            'frame-ancestors '.$this->origins->frameAncestors($website),
        ]);

        return response()
            ->view('widget.frame', [
                'config' => $this->config->frame($website),
                'nonce' => $nonce,
                'realtimeScript' => $this->realtime->enabled() ? asset('vendor/realtime/loop-realtime.js') : null,
            ])
            ->header('Content-Security-Policy', $csp)
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
