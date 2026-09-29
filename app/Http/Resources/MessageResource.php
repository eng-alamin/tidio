<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            'attachments' => $this->attachments,
            'is_private_note' => $this->is_private_note,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
