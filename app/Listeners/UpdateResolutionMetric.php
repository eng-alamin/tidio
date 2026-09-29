<?php

namespace App\Listeners;

use App\Events\ConversationResolved;
use App\Models\ConversationMetric;

class UpdateResolutionMetric
{
    public function handle(ConversationResolved $event): void
    {
        $conversation = $event->conversation;
        $metric = ConversationMetric::firstOrCreate(['conversation_id' => $conversation->id]);

        $resolutionSeconds = max(0, $conversation->created_at->diffInSeconds(now(), true));
        $slaBreached = $conversation->sla
            && $resolutionSeconds > ($conversation->sla->resolution_minutes * 60);

        $metric->update([
            'resolved_at' => now(),
            'resolution_seconds' => $resolutionSeconds,
            'sla_breached' => $slaBreached,
        ]);
    }
}