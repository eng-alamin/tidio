<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_type' => $this->channel_type,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'subject' => $this->subject,
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'assigned_operator' => $this->whenLoaded('assignedOperator', fn () => [
                'id' => $this->assignedOperator->id,
                'name' => $this->assignedOperator->name,
            ]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
