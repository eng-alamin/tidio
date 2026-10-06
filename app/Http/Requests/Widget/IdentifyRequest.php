<?php

namespace App\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // visitor is authenticated by ResolveWidgetVisitor
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim(strip_tags($this->input('name'))) : null,
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120', 'required_without:email'],
            'email' => ['nullable', 'email:rfc', 'max:190', 'required_without:name'],
        ];
    }
}
