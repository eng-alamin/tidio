<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(public Conversation $conversation)
    {
    }
}
