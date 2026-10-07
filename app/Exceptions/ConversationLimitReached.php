<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when a visitor tries to START a new conversation but the workspace has used up this
 * month's `conversations` allowance. Conversations that are already open are never affected.
 */
class ConversationLimitReached extends RuntimeException
{
    public function __construct(string $message = 'This chat cannot accept new conversations right now.')
    {
        parent::__construct($message);
    }

    /** Shape the widget understands: it shows its own translated notice for `conversation_limit`. */
    public function render(): JsonResponse
    {
        return response()
            ->json(['message' => $this->getMessage(), 'code' => 'conversation_limit'], 403)
            ->header('Cache-Control', 'no-store');
    }
}
