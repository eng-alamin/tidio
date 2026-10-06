<?php

namespace App\Jobs;

use App\Services\Widget\LyroWidgetResponder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Lets Lyro answer one visitor message from the chat widget.
 *
 * Dispatched with dispatchAfterResponse() by default (runs right after the visitor's HTTP
 * response is sent, so no queue worker is needed) or onto the queue when
 * WIDGET_LYRO_DISPATCH=queue.
 */
class ReplyWithLyro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly int $messageId)
    {
    }

    public function handle(LyroWidgetResponder $responder): void
    {
        $responder->respond($this->messageId);
    }
}
