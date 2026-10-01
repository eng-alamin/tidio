<?php

namespace App\Notifications;

use App\Models\Mention;
use Illuminate\Notifications\Notification;

class MentionedInConversation extends Notification
{
    public function __construct(private readonly Mention $mention)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = $this->mention->message;
        $conversation = $message?->conversation;

        return [
            'mention_id' => $this->mention->id,
            'conversation_id' => $conversation?->id,
            'by' => $message?->sender?->name ?? 'Someone',
            'excerpt' => str($message?->body ?? '')->limit(80)->toString(),
        ];
    }
}