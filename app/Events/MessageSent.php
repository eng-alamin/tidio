<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a message row is created. It is an in-process event only (metrics listener,
 * real-time signal listener). It used to implement ShouldBroadcast and push the whole Message —
 * private notes included — to a public channel; that is intentionally gone. Real-time pushes
 * now go through ConversationSignal, which carries no message content.
 */
class MessageSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
    }
}
