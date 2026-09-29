<?php

namespace App\Observers;

use App\Enums\ConversationStatus;
use App\Events\ConversationResolved;
use App\Models\Conversation;

class ConversationObserver
{
    // ConversationCreated fires via $dispatchesEvents on the model itself
    // (simple CRUD event, no extra logic needed). ConversationResolved is a
    // STATE TRANSITION though — "status changed TO solved" — which
    // $dispatchesEvents can't express, hence a dedicated Observer here.
    public function updated(Conversation $conversation): void
    {
        if (
            $conversation->wasChanged('status')
            && $conversation->status === ConversationStatus::Solved
        ) {
            ConversationResolved::dispatch($conversation);
        }
    }
}
