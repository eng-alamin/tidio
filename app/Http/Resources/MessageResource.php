<?php

namespace App\Http\Resources;

use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attachments = app(AttachmentService::class);

        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            // Never expose the internal storage path: id / name / kind / size / mime only.
            'attachments' => collect($this->attachments ?? [])
                ->filter(fn ($d) => is_array($d) && isset($d['id']))
                ->map(fn (array $d) => $attachments->publicShape($d))
                ->values()
                ->all(),
            'is_private_note' => $this->is_private_note,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
