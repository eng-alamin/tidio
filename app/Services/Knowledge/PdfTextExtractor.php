<?php

namespace App\Services\Knowledge;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Reads the text out of a PDF.
 *
 * Uses the smalot/pdfparser package when it is installed (`composer require smalot/pdfparser`),
 * otherwise the `pdftotext` command if the server has it. Scanned PDFs (pictures of pages) hold
 * no text, so they are reported instead of being silently accepted as empty knowledge.
 */
class PdfTextExtractor
{
    /**
     * @return array{title: string, text: string, pages: int}
     *
     * @throws CrawlException
     */
    public function extract(string $bytes): array
    {
        if (! str_starts_with(ltrim($bytes), '%PDF-')) {
            throw new CrawlException('That file is not a valid PDF.');
        }

        $result = class_exists(\Smalot\PdfParser\Parser::class)
            ? $this->withSmalot($bytes)
            : $this->withPdftotext($bytes);

        $text = $this->clean($result['text']);

        if (mb_strlen($text) < 20) {
            throw new CrawlException('No readable text was found in this PDF (it may be scanned pages saved as pictures).');
        }

        return ['title' => $this->oneLine($result['title']), 'text' => $text, 'pages' => $result['pages']];
    }

    /** @return array{title: string, text: string, pages: int} */
    private function withSmalot(string $bytes): array
    {
        try {
            $pdf = (new \Smalot\PdfParser\Parser)->parseContent($bytes);
            $details = $pdf->getDetails();
            $title = $details['Title'] ?? '';

            return [
                'title' => is_array($title) ? (string) ($title[0] ?? '') : (string) $title,
                'text' => $pdf->getText(),
                'pages' => count($pdf->getPages()),
            ];
        } catch (Throwable) {
            throw new CrawlException('This PDF could not be read (it may be damaged or password-protected).');
        }
    }

    /** @return array{title: string, text: string, pages: int} */
    private function withPdftotext(string $bytes): array
    {
        $binary = (new ExecutableFinder)->find('pdftotext');

        if (! $binary) {
            throw new CrawlException('PDF reading is not set up on this server. Ask your developer to run: composer require smalot/pdfparser');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'lyro-pdf-');

        try {
            file_put_contents($tmp, $bytes);
            $process = new Process([$binary, '-enc', 'UTF-8', $tmp, '-']);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new CrawlException('This PDF could not be read (it may be damaged or password-protected).');
            }

            $text = $process->getOutput();

            return ['title' => '', 'text' => $text, 'pages' => max(1, substr_count($text, "\f"))];
        } finally {
            @unlink($tmp);
        }
    }

    private function clean(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        $text = str_replace(["\r", "\f", "\xC2\xA0"], ["", "\n\n", ' '], $text);
        $text = preg_replace('/[^\P{C}\n\t]+/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function oneLine(string $text): string
    {
        $text = mb_check_encoding($text, 'UTF-8') ? $text : mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
