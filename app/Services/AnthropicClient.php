<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Anthropic's Messages API. Used by LyroAiEngine to
 * power the Lyro AI Agent Playground (and, later, real customer chat).
 */
class AnthropicClient
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $model = 'claude-sonnet-4-6',
    ) {
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey ?? config('services.anthropic.api_key'));
    }

    /**
     * @param  string  $system  System prompt (persona, tone, knowledge context).
     * @param  array<int, array{role: string, content: string}>  $messages  Conversation history, oldest first.
     * @return string The assistant's reply text.
     *
     * @throws RuntimeException on missing config or a non-2xx API response.
     */
    public function reply(string $system, array $messages, int $maxTokens = 1000): string
    {
        $apiKey = $this->apiKey ?? config('services.anthropic.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Anthropic API error ('.$response->status().'): '.($response->json('error.message') ?? $response->body())
            );
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        if ($text === '') {
            throw new RuntimeException('Anthropic API returned an empty reply.');
        }

        return $text;
    }
}