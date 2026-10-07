<?php

namespace App\Services\Knowledge;

/**
 * Splits text into passages and ranks them against a question (BM25). No database, no framework:
 * plain functions so it is easy to test. Works for any language that separates words with
 * spaces/punctuation (English, Bengali, ...).
 */
class PassageRanker
{
    private const STOPWORDS = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'your', 'can', 'how', 'what', 'when', 'where', 'who', 'why', 'does', 'did', 'have', 'has', 'had', 'with', 'this', 'that', 'from', 'about', 'into', 'will', 'would', 'could', 'should', 'there', 'their', 'they', 'them', 'than', 'then', 'also', 'any', 'all', 'our', 'out', 'get', 'got', 'its', 'was', 'were', 'been', 'being', 'please', 'hello', 'thanks', 'thank', 'hi', 'is', 'it', 'of', 'to', 'in', 'on', 'at', 'a', 'an', 'be', 'do', 'if', 'or', 'as', 'by', 'my', 'me', 'we', 'us', 'i'];

    /**
     * Cuts text into passages of roughly $size characters, on paragraph boundaries where possible.
     *
     * @return array<int, string>
     */
    public function chunk(string $text, int $size = 900): array
    {
        $chunks = [];
        $buffer = '';

        foreach (preg_split('/\n{2,}/', trim($text)) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            // A paragraph longer than a passage is cut at sentence ends, then by length.
            foreach ($this->split($paragraph, $size) as $piece) {
                if ($buffer !== '' && mb_strlen($buffer) + mb_strlen($piece) + 2 > $size) {
                    $chunks[] = $buffer;
                    $buffer = '';
                }

                $buffer .= ($buffer === '' ? '' : "\n\n").$piece;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }

    /** @return array<int, string> */
    private function split(string $paragraph, int $size): array
    {
        if (mb_strlen($paragraph) <= $size) {
            return [$paragraph];
        }

        $pieces = [];
        $buffer = '';

        foreach (preg_split('/(?<=[.!?।])\s+/u', $paragraph) ?: [$paragraph] as $sentence) {
            while (mb_strlen($sentence) > $size) {
                if ($buffer !== '') {
                    $pieces[] = $buffer;
                    $buffer = '';
                }

                $pieces[] = mb_substr($sentence, 0, $size);
                $sentence = mb_substr($sentence, $size);
            }

            if ($buffer !== '' && mb_strlen($buffer) + mb_strlen($sentence) + 1 > $size) {
                $pieces[] = $buffer;
                $buffer = '';
            }

            $buffer .= ($buffer === '' ? '' : ' ').$sentence;
        }

        if ($buffer !== '') {
            $pieces[] = $buffer;
        }

        return $pieces;
    }

    /** @return array<int, string> */
    public function tokens(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}\p{M}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 2 || in_array($word, self::STOPWORDS, true)) {
                continue;
            }

            // Cheap English plural folding so "refunds" finds "refund".
            if (mb_strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
                $word = mb_substr($word, 0, -1);
            }

            $out[] = $word;
        }

        return $out;
    }

    /**
     * Best passages for a question, highest score first. Passages with no matching word are left out.
     *
     * @param  array<int, string>  $passages
     * @return array<int, array{index: int, score: float}>
     */
    public function rank(array $passages, string $query, int $limit = 12): array
    {
        $terms = array_values(array_unique($this->tokens($query)));

        if ($terms === [] || $passages === []) {
            return [];
        }

        $docs = [];
        $lengths = [];
        $docFreq = array_fill_keys($terms, 0);

        foreach ($passages as $i => $passage) {
            $counts = array_count_values($this->tokens($passage));
            $docs[$i] = $counts;
            $lengths[$i] = array_sum($counts);

            foreach ($terms as $term) {
                if (isset($counts[$term])) {
                    $docFreq[$term]++;
                }
            }
        }

        $n = count($passages);
        $avg = max(1.0, array_sum($lengths) / $n);
        $k1 = 1.5;
        $b = 0.75;
        $scored = [];

        foreach ($docs as $i => $counts) {
            $score = 0.0;

            foreach ($terms as $term) {
                $tf = $counts[$term] ?? 0;

                if ($tf === 0) {
                    continue;
                }

                $idf = log(1 + ($n - $docFreq[$term] + 0.5) / ($docFreq[$term] + 0.5));
                $score += $idf * ($tf * ($k1 + 1)) / ($tf + $k1 * (1 - $b + $b * $lengths[$i] / $avg));
            }

            if ($score > 0) {
                $scored[] = ['index' => $i, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }
}
