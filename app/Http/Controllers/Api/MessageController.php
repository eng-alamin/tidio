<?php

namespace App\Http\Controllers\Api;

use App\Enums\MessageSenderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Mention;
use App\Models\Workspace;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\DB;
use Throwable;

class MessageController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments)
    {
    }

    public function index(Workspace $workspace, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        return MessageResource::collection($conversation->messages()->oldest()->paginate(50));
    }

    public function store(StoreMessageRequest $request, Workspace $workspace, Conversation $conversation)
    {
        $this->authorize('reply', $conversation);

        $stored = [];

        DB::beginTransaction();

        try {
            // Uploaded files go to the private disk; only their descriptors are saved on the message.
            $stored = $this->attachments->store(
                array_values($request->file('attachments', [])),
                $conversation->workspace_id,
                $conversation->id,
            );

            $message = $conversation->messages()->create([
                'sender_type' => MessageSenderType::Operator,
                'sender_id' => $request->user()->id,
                'body' => $request->body,
                'attachments' => $stored ?: null,
                'is_private_note' => $request->boolean('is_private_note'),
            ]);

            foreach ($request->input('mentioned_user_ids', []) as $userId) {
                Mention::create(['message_id' => $message->id, 'mentioned_user_id' => $userId]);
            }

            $conversation->update(['last_message_at' => $message->created_at]);

            activity('api')
                ->performedOn($message)
                ->event('created')
                ->withProperties(['conversation_id' => $conversation->id, 'attachments' => count($stored)])
                ->log('Message sent through the API');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->attachments->discard($stored);

            throw $e;
        }

        return new MessageResource($message);
    }
}
