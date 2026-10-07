<?php

namespace App\Http\Requests\Widget;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // visitor is authenticated by ResolveWidgetVisitor
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');

        if (is_string($body)) {
            $body = str_replace(["\r\n", "\r"], "\n", $body);
            $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body) ?? '';  // control chars
            $body = preg_replace("/\n{3,}/", "\n\n", $body) ?? '';
            $this->merge(['body' => trim($body)]);
        }
    }

    public function rules(): array
    {
        return [
            // A message may be files only, but never completely empty.
            'body' => ['nullable', 'required_without:files', 'string', 'max:'.(int) config('widget.message_max_length')],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ] + app(AttachmentService::class)->rules('files');
    }
}
