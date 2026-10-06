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

    // Requests per minute, per visitor session (or IP when there is no session yet).
    'rate_limits' => [
        'asset' => 120,
        'init' => 30,
        'poll' => 90,
        'send' => 20,
        'identify' => 10,
    ],
];
