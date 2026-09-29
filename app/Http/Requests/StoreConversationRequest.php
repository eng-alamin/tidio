<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // workspace scoping/membership is enforced by the controller + policy, not here
    }

    public function rules(): array
    {
        return [
            'channel_type' => ['required', 'in:widget,whatsapp,messenger,instagram,email'],
            'channel_id' => ['nullable', 'exists:channels,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'visitor_id' => ['nullable', 'exists:visitors,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'type' => ['required', 'in:chat,ticket'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'subject' => ['nullable', 'string', 'max:255', 'required_if:type,ticket'],
        ];
    }
}
