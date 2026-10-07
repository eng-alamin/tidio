<?php

namespace App\Services\Knowledge;

use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use App\Models\AiDataSource;
use App\Models\Workspace;

/**
 * Picks the parts of the crawled websites / PDFs that are relevant to the visitor's question, so
 * Lyro reads a few thousand characters instead of every page ever crawled.
 */
class KnowledgeRetriever
{
    public function __construct(private readonly PassageRanker $ranker)
    {
    }

    /**
     * @return array{text: string, source_ids: array<int, int>}
     */
    public function passages(Workspace $workspace, string $query): array
    {
        $empty = ['text' => '', 'source_ids' => []];

        if (trim($query) === '') {
            return $empty;
        }

        $sources = AiDataSource::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', AiDataSourceStatus::Synced)
            ->whereIn('type', [AiDataSourceType::Url->value, AiDataSourceType::Pdf->value, AiDataSourceType::HelpCenter->value])
            ->whereNotNull('content')
            ->get(['id', 'type', 'source', 'title', 'content']);

        if ($sources->isEmpty()) {
            return $empty;
        }

        $chunkSize = (int) config('lyro.retrieval.chunk_chars', 900);
        $maxChunks = (int) config('lyro.retrieval.max_chunks', 6000);
        $budget = (int) config('lyro.retrieval.budget_chars', 12000);

        $passages = [];
        $owner = [];

        foreach ($sources as $source) {
            foreach ($this->ranker->chunk((string) $source->content, $chunkSize) as $chunk) {
                if (count($passages) >= $maxChunks) {
                    break 2;
                }

                $passages[] = $chunk;
                $owner[] = $source->id;
            }
        }

        $best = $this->ranker->rank($passages, $query, 40);
        $picked = []; // source id => [passage, ...]
        $used = 0;

        foreach ($best as $hit) {
            $text = $passages[$hit['index']];

            if ($used + mb_strlen($text) > $budget && $used > 0) {
                continue; // a smaller, lower-ranked passage may still fit
            }

            $picked[$owner[$hit['index']]][] = $text;
            $used += mb_strlen($text);
        }

        if ($picked === []) {
            return $empty;
        }

        $blocks = [];

        foreach ($sources as $source) {
            if (! isset($picked[$source->id])) {
                continue;
            }

            $name = $this->plain($source->title ?: $source->source);
            $origin = $this->plain($source->source);
            $body = $this->plain(implode("\n\n", $picked[$source->id]));

            $blocks[] = "<source name=\"{$name}\" origin=\"{$origin}\">\n{$body}\n</source>";
        }

        return ['text' => implode("\n\n", $blocks), 'source_ids' => array_keys($picked)];
    }

    /** Crawled text is untrusted: it must not be able to close our <source> tags or fake new ones. */
    private function plain(string $text): string
    {
        return str_replace(['<', '>', '"'], ['‹', '›', "'"], $text);
    }
}
