<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lyro knowledge crawling (Data sources: Website / File)
    |--------------------------------------------------------------------------
    | dispatch:      'after_response' syncs right after the request that added the source
    |                (no queue worker needed); 'queue' uses the queue (php artisan queue:work).
    | max_pages:     most pages read for one Website source (the page itself + same-site links).
    | depth:         0 = only the page you entered, 1 = also pages it links to (same site only).
    | max_chars:     text kept per data source (anything beyond is cut).
    | max_bytes:     largest single download (HTML page or PDF).
    | allow_private: lets the crawler reach localhost / private network addresses. KEEP FALSE in
    |                production — it is the protection against server-side request forgery.
    */
    'crawl' => [
        'dispatch' => env('LYRO_CRAWL_DISPATCH', 'after_response'),
        'max_pages' => (int) env('LYRO_CRAWL_MAX_PAGES', 10),
        'depth' => (int) env('LYRO_CRAWL_DEPTH', 1),
        'max_chars' => 300000,
        'max_bytes' => 10 * 1024 * 1024,
        'timeout' => 15,
        'delay_ms' => (int) env('LYRO_CRAWL_DELAY_MS', 250), // pause between pages of one site
        'max_redirects' => 3,
        'user_agent' => 'LyroBot/1.0 (+knowledge sync for your own chat widget)',
        'allow_private' => (bool) env('LYRO_CRAWL_ALLOW_PRIVATE', false),
    ],

    /*
    | How much crawled text is handed to Lyro per question. Only the passages that match the
    | visitor's question are included, up to this many characters.
    */
    'retrieval' => [
        'budget_chars' => 12000,
        'chunk_chars' => 900,
        'max_chunks' => 6000,
    ],

];
