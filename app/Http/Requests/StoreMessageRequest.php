<?php

namespace App\Http\Requests;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:10000'],
            'is_private_note' => ['sometimes', 'boolean'],
            'mentioned_user_ids' => ['sometimes', 'array'],
            'mentioned_user_ids.*' => ['exists:users,id'],
        ] + app(AttachmentService::class)->rules('attachments'); // allowed types, count and size come from config/widget.php
    }
}
