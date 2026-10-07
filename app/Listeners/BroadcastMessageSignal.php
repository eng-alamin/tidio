<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Services\Realtime\RealtimeSignals;

/** Turns every new message (visitor, operator, bot, note) into a real-time "conversation changed" signal. */
class BroadcastMessageSignal
{
    public function __construct(private readonly RealtimeSignals $signals)
    {
    }

    public function handle(MessageSent $event): void
    {
        if (! $this->signals->enabled()) {
            return; // polling mode: don't even spend a query
        }

        $message = $event->message;

        $conversation = Conversation::query()
            ->select(['id', 'workspace_id', 'visitor_id'])
            ->find($message->conversation_id);

        if (! $conversation) {
            return;
        }

        $this->signals->conversationChanged(
            $conversation->workspace_id,
            $conversation->id,
            $conversation->visitor_id,
            'message',
            ! $message->is_private_note,
        );
    }
}
