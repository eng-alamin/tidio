<?php

namespace App\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class PollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // visitor is authenticated by ResolveWidgetVisitor
    }

    public function rules(): array
    {
        return [
            'after' => ['nullable', 'integer', 'min:0'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
