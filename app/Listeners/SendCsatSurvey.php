<?php

namespace App\Listeners;

use App\Enums\CsatTrigger;
use App\Events\ConversationResolved;
use App\Models\CsatSetting;

class SendCsatSurvey
{
    public function handle(ConversationResolved $event): void
    {
        $conversation = $event->conversation;

        $settings = CsatSetting::where('workspace_id', $conversation->workspace_id)->first();

        if (! $settings || ! $settings->is_enabled || $settings->trigger !== CsatTrigger::AfterResolution) {
            return;
        }

        // Hand off to your notification/widget-push layer — CSAT collection
        // itself (writing to csat_ratings) happens when the customer responds,
        // via a separate controller endpoint, not here.
        // Notification::send($conversation->contact, new CsatSurveyRequested($conversation));
    }
}
