<?php

namespace App\Services;

use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use App\Models\AiAgentSetting;
use App\Models\AiDataSource;
use App\Models\Workspace;
use RuntimeException;

/**
 * The real thing Playground's "resolveTestReply" was a placeholder for.
 * Builds a system prompt out of this workspace's actual Guidance/tone
 * settings and synced Data sources, then calls Anthropic's API.
 *
 * Only FAQ-type sources have real crawled content (stored as
 * "question\nanswer" — see Lyro::saveAnswer()); URL/PDF/help-center
 * sources currently only store a reference (a URL or filename), since
 * real crawling/parsing isn't built yet, so they're listed as topic
 * hints rather than pasted in as content.
 */
class LyroAiEngine
{
    public function __construct(private readonly AnthropicClient $client)
    {
    }

    public function isAvailable(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @param  array<int, array{from: string, text: string}>  $history  Prior turns ('me'/'bot'), oldest first.
     */
    public function reply(Workspace $workspace, string $question, array $history = [], string $channel = 'live'): string
    {
        if (! $this->isAvailable()) {
            return "No AI engine is connected yet — set ANTHROPIC_API_KEY to enable Lyro's real replies. This is a placeholder response.";
        }

        $system = $this->buildSystemPrompt($workspace, $channel);
        $messages = $this->buildMessages($history, $question);

        try {
            return $this->client->reply($system, $messages);
        } catch (RuntimeException $e) {
            report($e);

            return "Lyro couldn't generate a reply right now ({$e->getMessage()}). Please try again.";
        }
    }

    private function buildSystemPrompt(Workspace $workspace, string $channel): string
    {
        $setting = AiAgentSetting::where('workspace_id', $workspace->id)->first();

        $agentName = $setting?->agent_name ?: 'Lyro';
        $tone = $setting?->tone ?: 'friendly';
        $instructions = $setting?->guidance_instructions;
        $language = $setting?->default_language ?: 'auto';

        $lines = [
            "You are {$agentName}, an AI customer support agent for the business \"{$workspace->name}\".",
            "Tone: {$tone}. Keep replies concise and helpful, suited for a {$channel} chat.",
        ];

        if ($language && $language !== 'auto') {
            $lines[] = "Always reply in: {$language}.";
        }

        if (filled($instructions)) {
            $lines[] = "Additional instructions from the business owner: {$instructions}";
        }

        $knowledge = $this->buildKnowledgeContext($workspace);
        if ($knowledge !== '') {
            $lines[] = "\nKnowledge base:\n{$knowledge}";
        }

        $lines[] = "\nIf you don't know the answer from the knowledge base above, say so honestly and offer to connect the visitor with a human — never invent facts about the business.";

        return implode("\n", $lines);
    }

    private function buildKnowledgeContext(Workspace $workspace): string
    {
        $sources = AiDataSource::where('workspace_id', $workspace->id)
            ->where('status', AiDataSourceStatus::Synced)
            ->get();

        $faqBlocks = [];
        $topicHints = [];

        foreach ($sources as $source) {
            if ($source->type === AiDataSourceType::Faq) {
                [$q, $a] = array_pad(explode("\n", $source->source, 2), 2, null);
                if ($q && $a) {
                    $faqBlocks[] = "Q: {$q}\nA: {$a}";
                }
            } else {
                // URL/PDF/help-center: only a reference is stored, not crawled
                // content, so just hint at the topic rather than fabricate content.
                $topicHints[] = "- {$source->type->value}: {$source->source}";
            }
        }

        $out = '';
        if ($faqBlocks) {
            $out .= implode("\n\n", array_slice($faqBlocks, 0, 20));
        }
        if ($topicHints) {
            $out .= "\n\n(Other referenced sources — not yet crawled, topic only:\n".implode("\n", array_slice($topicHints, 0, 10)).')';
        }

        return trim($out);
    }

    /**
     * @param  array<int, array{from: string, text: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(array $history, string $question): array
    {
        $messages = [];

        foreach ($history as $turn) {
            $messages[] = [
                'role' => $turn['from'] === 'me' ? 'user' : 'assistant',
                'content' => $turn['text'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }
}