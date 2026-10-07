<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * "Something changed in conversation N" — nothing more. The payload has NO message text, names or
 * files, so a socket can never leak content; clients re-fetch through the normal authorised
 * endpoints. Operators hear it on `private-workspace.{id}`. The visitor hears it on
 * `private-widget.visitor.{id}` only when the change is visible to them (never for internal notes).
 */
class ConversationSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $workspaceId,
        public int $conversationId,
        public ?int $visitorId,
        public string $reason,
        public bool $visitorVisible,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('workspace.'.$this->workspaceId)];

        if ($this->visitorVisible && $this->visitorId !== null) {
            $channels[] = new PrivateChannel('widget.visitor.'.$this->visitorId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'conversation.signal';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'reason' => $this->reason];
    }
}
