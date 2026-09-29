<?php

namespace App\Http\Controllers\Api;

use App\Enums\MessageSenderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Mention;
use App\Models\Workspace;

class MessageController extends Controller
{
    public function index(Workspace $workspace, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        return MessageResource::collection($conversation->messages()->oldest()->paginate(50));
    }

    public function store(StoreMessageRequest $request, Workspace $workspace, Conversation $conversation)
    {
        $this->authorize('reply', $conversation);

        $message = $conversation->messages()->create([
            'sender_type' => MessageSenderType::Operator,
            'sender_id' => $request->user()->id,
            'body' => $request->body,
            'attachments' => $request->attachments,
            'is_private_note' => $request->boolean('is_private_note'),
        ]);

        foreach ($request->input('mentioned_user_ids', []) as $userId) {
            Mention::create(['message_id' => $message->id, 'mentioned_user_id' => $userId]);
        }

        $conversation->update(['last_message_at' => $message->created_at]);

        return new MessageResource($message);
    }
}
