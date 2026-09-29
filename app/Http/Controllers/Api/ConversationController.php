<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\UpdateConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Workspace $workspace, Request $request)
    {
        $conversations = $workspace->conversations()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->channel_type, fn ($q) => $q->where('channel_type', $request->channel_type))
            ->when($request->assigned_to_me, fn ($q) => $q->where('assigned_operator_id', $request->user()->id))
            ->with(['contact', 'assignedOperator', 'tags'])
            ->latest('last_message_at')
            ->paginate(25);

        return ConversationResource::collection($conversations);
    }

    public function store(StoreConversationRequest $request, Workspace $workspace)
    {
        $conversation = $workspace->conversations()->create($request->validated());

        return new ConversationResource($conversation);
    }

    public function show(Workspace $workspace, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        return new ConversationResource($conversation->load(['contact', 'assignedOperator', 'tags', 'messages']));
    }

    public function update(UpdateConversationRequest $request, Workspace $workspace, Conversation $conversation)
    {
        $conversation->update($request->validated());

        return new ConversationResource($conversation);
    }

    public function destroy(Workspace $workspace, Conversation $conversation)
    {
        $this->authorize('delete', $conversation);
        $conversation->delete();

        return response()->noContent();
    }
}
