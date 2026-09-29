<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return app(ConversationPolicy::class)->view($user, $message->conversation);
    }

    public function create(User $user, Message $message): bool
    {
        return app(ConversationPolicy::class)->reply($user, $message->conversation);
    }

    public function delete(User $user, Message $message): bool
    {
        // Operators can only delete their own messages; private notes and
        // visitor/bot messages are never author-deletable by an operator.
        return $message->sender_type === \App\Enums\MessageSenderType::Operator
            && (int) $message->sender_id === $user->id;
    }
}
