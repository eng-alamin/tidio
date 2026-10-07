<?php

namespace App\Services\Knowledge;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Turns an HTML page into the readable text a support agent needs: title, headings, paragraphs,
 * lists and tables — without scripts, styles, menus, footers or forms. Also returns the page's
 * links (taken BEFORE menus are stripped) so the crawler can follow them.
 */
class HtmlTextExtractor
{
    /** Elements whose content is never useful as knowledge. */
    private const DROP = ['script', 'style', 'noscript', 'svg', 'iframe', 'canvas', 'template', 'nav', 'footer', 'aside', 'form', 'button', 'select', 'input', 'textarea', 'head'];

    /** Elements that start a new line of text. */
    private const BLOCK = ['p', 'div', 'section', 'article', 'main', 'header', 'ul', 'ol', 'li', 'table', 'tr', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'dd', 'dt', 'dl', 'figure', 'figcaption', 'details', 'summary', 'address', 'hr'];

    /**
     * @return array{title: string, text: string, links: array<int, string>}
     */
    public function extract(string $html, string $baseUrl = ''): array
    {
        $dom = $this->load($html);

        if (! $dom) {
            return ['title' => '', 'text' => '', 'links' => []];
        }

        $xpath = new DOMXPath($dom);

        $title = trim((string) $xpath->evaluate('string(//title)'));
        $description = trim((string) $xpath->evaluate('string(//meta[translate(@name,"DESCRIPTION","description")="description"]/@content)'));
        $links = $this->links($xpath, $baseUrl);

        foreach (self::DROP as $tag) {
            if ($tag === 'head') {
                continue; // <head> holds the title; it is skipped below by choosing a body root
            }

            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $root = $this->contentRoot($xpath) ?? $dom->documentElement;

        $buffer = '';
        $this->walk($root, $buffer);

        $text = $this->tidy($buffer);

        if ($description !== '' && ! str_contains($text, $description)) {
            $text = $description."\n\n".$text;
        }

        return ['title' => $this->oneLine($title), 'text' => trim($text), 'links' => $links];
    }

    private function load(string $html): ?DOMDocument
    {
        if (trim($html) === '') {
            return null;
        }

        $html = $this->toUtf8($html);
        // We now hold UTF-8; a leftover <meta charset=...> would make libxml re-decode it wrongly.
        $html = preg_replace('/<meta\b[^>]*charset[^>]*>/i', '', $html) ?? $html;

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // The XML declaration makes libxml read the markup as UTF-8 whatever the <meta> says.
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $dom : null;
    }

    /** Best-effort conversion of legacy encodings (declared charset) to UTF-8. */
    private function toUtf8(string $html): string
    {
        if (mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }

        if (preg_match('/charset\s*=\s*["\']?([\w\-]+)/i', substr($html, 0, 2048), $m)) {
            $converted = @mb_convert_encoding($html, 'UTF-8', $m[1]);

            if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8'); // drops invalid bytes
    }

    private function contentRoot(DOMXPath $xpath): ?DOMNode
    {
        foreach (['//main', '//article', '//*[@role="main"]', '//body'] as $query) {
            $nodes = $xpath->query($query);

            if ($nodes && $nodes->length > 0) {
                return $nodes->item(0);
            }
        }

        return null;
    }

    /** @return array<int, string> absolute http(s) URLs, no fragments, de-duplicated */
    private function links(DOMXPath $xpath, string $baseUrl): array
    {
        $out = [];

        foreach ($xpath->query('//a[@href]') ?: [] as $a) {
            $href = trim($a->getAttribute('href'));

            if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel|javascript|data):/i', $href)) {
                continue;
            }

            $absolute = $baseUrl !== '' ? self::resolve($baseUrl, $href) : $href;

            if ($absolute !== null && preg_match('#^https?://#i', $absolute)) {
                $out[$absolute] = true;
            }
        }

        return array_keys($out);
    }

    /** Resolves $href against $base (RFC 3986, good enough for crawling). Fragment is dropped. */
    public static function resolve(string $base, string $href): ?string
    {
        $href = preg_replace('/#.*$/', '', $href);

        if ($href === '' || $href === null) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $href)) {
            return $href;
        }

        $b = parse_url($base);

        if (! $b || ! isset($b['scheme'], $b['host'])) {
            return null;
        }

        $origin = $b['scheme'].'://'.$b['host'].(isset($b['port']) ? ':'.$b['port'] : '');

        if (str_starts_with($href, '//')) {
            return $b['scheme'].':'.$href;
        }

        if (str_starts_with($href, '/')) {
            return $origin.self::normalizePath($href);
        }

        $path = $b['path'] ?? '/';
        $dir = substr($path, 0, (int) strrpos($path, '/') + 1) ?: '/';

        if (str_starts_with($href, '?')) {
            return $origin.$path.$href;
        }

        return $origin.self::normalizePath($dir.$href);
    }

    private static function normalizePath(string $path): string
    {
        $query = '';

        if (($pos = strpos($path, '?')) !== false) {
            $query = substr($path, $pos);
            $path = substr($path, 0, $pos);
        }

        $out = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($out);
            } elseif ($segment !== '.') {
                $out[] = $segment;
            }
        }

        $result = implode('/', $out);

        return ($result === '' ? '/' : $result).$query;
    }

    private function walk(DOMNode $node, string &$out): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $out .= $child->nodeValue;

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            $isBlock = in_array($tag, self::BLOCK, true);
            $heading = preg_match('/^h[1-6]$/', $tag) === 1;

            if ($isBlock) {
                $this->newline($out, $heading);
            }

            if ($tag === 'li') {
                $out .= '- ';
            }

            if ($tag === 'td' || $tag === 'th') {
                $out .= ' | ';
            }

            $this->walk($child, $out);

            if ($isBlock) {
                $this->newline($out, $heading);
            }
        }
    }

    /** Ends the current line (or paragraph) without stacking up empty lines. */
    private function newline(string &$out, bool $paragraph): void
    {
        $out = rtrim($out, " \t");
        $want = $paragraph ? "\n\n" : "\n";

        if ($out === '' || str_ends_with($out, $want)) {
            return;
        }

        $out .= str_ends_with($out, "\n") ? "\n" : $want;
    }

    private function tidy(string $text): string
    {
        $text = str_replace(["\xC2\xA0", "\r"], [' ', ''], $text);
        $text = preg_replace('/[^\P{C}\n\t]+/u', '', $text) ?? $text;   // control chars
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function oneLine(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
