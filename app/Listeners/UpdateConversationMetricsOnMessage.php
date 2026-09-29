<?php

namespace App\Listeners;

use App\Enums\MessageSenderType;
use App\Models\ConversationMetric;
use App\Events\MessageSent;

class UpdateConversationMetricsOnMessage
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        if (! in_array($message->sender_type, [MessageSenderType::Operator, MessageSenderType::Bot], true)) {
            return;
        }

        $metric = ConversationMetric::firstOrCreate(['conversation_id' => $message->conversation_id]);

        if ($metric->first_response_at !== null) {
            return;
        }

        $firstInbound = $message->conversation->messages()
            ->where('sender_type', MessageSenderType::Visitor->value)
            ->oldest()
            ->first();

        $metric->update([
            'first_response_at' => $message->created_at,
            'first_response_seconds' => $firstInbound
                ? max(0, $message->created_at->diffInSeconds($firstInbound->created_at, true))
                : null,
        ]);
    }
}