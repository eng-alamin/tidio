<?php

namespace App\Services\Realtime;

use App\Events\ConversationSignal;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Sends the tiny "a conversation changed" push. It runs only AFTER the surrounding database
 * transaction commits (so a client that reacts immediately sees the new data) and can never
 * fail the request that caused it: a down Reverb server is just logged.
 */
class RealtimeSignals
{
    public function __construct(private readonly RealtimeConfig $config)
    {
    }

    public function enabled(): bool
    {
        return $this->config->enabled();
    }

    public function conversationChanged(
        int $workspaceId,
        int $conversationId,
        ?int $visitorId,
        string $reason,
        bool $visitorVisible,
    ): void {
        if (! $this->config->enabled()) {
            return;
        }

        DB::afterCommit(function () use ($workspaceId, $conversationId, $visitorId, $reason, $visitorVisible): void {
            try {
                event(new ConversationSignal($workspaceId, $conversationId, $visitorId, $reason, $visitorVisible));
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
