<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reassign', $this->route('conversation'));
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:open,pending,solved,spam'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'assigned_operator_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
        ];
    }
}
