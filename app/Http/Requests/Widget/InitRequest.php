<?php

namespace App\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class InitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint; access is gated by ResolveWidgetWebsite + throttling
    }

    public function rules(): array
    {
        return [
            'session_id' => ['nullable', 'uuid'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
