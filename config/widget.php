<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Embeddable chat widget
    |--------------------------------------------------------------------------
    | allow_any_origin: skips the "only the website's own domain may embed me"
    | check. Keep it false in production. Turn it on locally (WIDGET_ALLOW_ANY_ORIGIN=true)
    | if you test the widget from a file:// page, which browsers send as Origin "null".
    */
    'allow_any_origin' => (bool) env('WIDGET_ALLOW_ANY_ORIGIN', false),

    // Longest visitor message accepted, in characters.
    'message_max_length' => 2000,

    // A visitor with no heartbeat for this long is flagged offline by `widget:mark-offline`.
    'online_ttl_seconds' => 120,

    /*
    | Lyro AI replies inside widget conversations.
    | enabled:  master switch (WIDGET_LYRO_ENABLED=false turns the bot off everywhere).
    | dispatch: 'after_response' runs right after the visitor's request finishes (no queue worker
    |           needed); 'queue' uses the queue (run `php artisan queue:work`).
    | history:  how many recent messages Lyro sees.
    */
    'lyro' => [
        'enabled' => (bool) env('WIDGET_LYRO_ENABLED', true),
        'dispatch' => env('WIDGET_LYRO_DISPATCH', 'after_response'),
        'history' => 20,
    ],

    /*
    | File attachments (visitor <-> operator).
    | Files live on a PRIVATE disk (never in /public) and are only served through authorised routes:
    | operators through the logged-in app, visitors through short-lived signed URLs.
    | max_kb is per file; the PHP limits `upload_max_filesize` / `post_max_size` must be at least
    | max_files x max_kb. SVG and HTML are deliberately NOT allowed (script injection).
    */
    'attachments' => [
        'enabled' => (bool) env('WIDGET_ATTACHMENTS_ENABLED', true),
        'disk' => env('WIDGET_ATTACHMENTS_DISK', 'local'),
        'max_files' => 3,
        'max_kb' => 5120,
        'url_ttl_minutes' => 360,
        'extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'txt', 'csv',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip',
        ],
    ],

    /*
    | Visitor country (used by Lyro's "Specific countries" audience and shown in the Inbox).
    | headers:         request headers that carry the visitor's ISO country code, set by your CDN or
    |                  reverse proxy. Use [] if you are not behind one (a visitor could fake the header).
    | database:        optional path to a MaxMind GeoLite2-Country.mmdb (needs `composer require geoip2/geoip2`).
    | default_country: fallback code for local testing, e.g. WIDGET_GEO_DEFAULT_COUNTRY=BD.
    */
    'geo' => [
        'enabled' => (bool) env('WIDGET_GEO_ENABLED', true),
        'headers' => array_values(array_filter(array_map('trim', explode(
            ',',
            (string) env('WIDGET_GEO_HEADERS', 'CF-IPCountry,CloudFront-Viewer-Country,X-Vercel-IP-Country')
        )))),
        'database' => env('WIDGET_GEO_DATABASE'),
        'default_country' => env('WIDGET_GEO_DEFAULT_COUNTRY'),
    ],

    /*
    | Real-time push through Laravel Reverb (WebSockets).
    | The socket only carries a tiny "something changed" signal (never message text or files);
    | clients then fetch the real data over the normal authorised HTTP endpoints. Polling stays as a
    | slow safety net, and takes over again at full speed if the socket drops.
    | Turns on only when BROADCAST_CONNECTION=reverb AND this switch is true.
    | public_*: the address the BROWSER connects to (may differ from the server-side REVERB_HOST).
    */
    'realtime' => [
        'enabled' => (bool) env('WIDGET_REALTIME_ENABLED', true),
        'public_host' => env('REVERB_PUBLIC_HOST', env('REVERB_HOST', 'localhost')),
        'public_port' => (int) env('REVERB_PUBLIC_PORT', env('REVERB_PORT', 8080)),
        'public_scheme' => env('REVERB_PUBLIC_SCHEME', env('REVERB_SCHEME', 'http')),
    ],

    // Requests per minute, per visitor session (or IP when there is no session yet).
    'rate_limits' => [
        'asset' => 120,
        'init' => 30,
        'poll' => 90,
        'send' => 20,
        'identify' => 10,
        'auth' => 30,
    ],
];
