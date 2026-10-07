<?php

namespace App\Services\Knowledge;

/**
 * Reads a web page and (optionally) the pages of the SAME website it links to, and returns one
 * block of text. Polite and bounded: a page limit, a depth limit, robots.txt for linked pages,
 * a short pause between pages, and every request goes through SafeFetcher (SSRF-safe).
 */
class WebsiteCrawler
{
    private const SKIP_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp', 'css', 'js', 'json', 'xml', 'rss', 'zip', 'gz', 'tar', 'rar', '7z', 'mp3', 'mp4', 'avi', 'mov', 'wmv', 'webm', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'exe', 'dmg', 'apk', 'iso', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    public function __construct(
        private readonly SafeFetcher $fetcher,
        private readonly HtmlTextExtractor $html,
        private readonly PdfTextExtractor $pdf,
    ) {
    }

    /**
     * @return array{title: string, text: string, pages: int}
     *
     * @throws CrawlException when not even the first page could be read
     */
    public function crawl(string $startUrl): array
    {
        $maxPages = max(1, (int) config('lyro.crawl.max_pages', 10));
        $maxDepth = max(0, (int) config('lyro.crawl.depth', 1));
        $maxChars = (int) config('lyro.crawl.max_chars', 300000);
        $delay = (int) config('lyro.crawl.delay_ms', 250);

        $queue = [[trim($startUrl), 0]];
        $seen = [$this->key($startUrl) => true];
        $blocks = [];
        $title = '';
        $chars = 0;
        $siteHost = null;
        $robots = RobotsRules::allowAll();
        $firstError = null;

        while ($queue && count($blocks) < $maxPages && $chars < $maxChars) {
            [$url, $depth] = array_shift($queue);
            $isFirst = $siteHost === null;

            if (! $isFirst && $delay > 0) {
                usleep($delay * 1000);
            }

            try {
                $page = $this->fetcher->get($url);
            } catch (CrawlException $e) {
                if ($isFirst) {
                    throw $e; // the page the owner typed must work
                }

                continue;
            }

            if ($isFirst) {
                $siteHost = $this->site((string) parse_url($page['url'], PHP_URL_HOST));
                $robots = $this->robotsFor($page['url']);
                $seen[$this->key($page['url'])] = true;
            }

            try {
                [$pageTitle, $text, $links] = $this->read($page);
            } catch (CrawlException $e) {
                if ($isFirst) {
                    throw $e;
                }

                continue;
            }

            if ($text === '') {
                $firstError ??= 'No readable text was found on that page (it may need JavaScript to show its content).';

                continue;
            }

            $title = $title !== '' ? $title : $pageTitle;
            $heading = $pageTitle !== '' ? $pageTitle : $page['url'];
            $block = "## {$heading} ({$page['url']})\n{$text}";

            $blocks[] = $block;
            $chars += mb_strlen($block);

            if ($depth < $maxDepth) {
                foreach ($links as $link) {
                    $key = $this->key($link);

                    if (isset($seen[$key]) || count($queue) >= 200 || ! $this->shouldFollow($link, $siteHost, $robots)) {
                        continue;
                    }

                    $seen[$key] = true;
                    $queue[] = [$link, $depth + 1];
                }
            }
        }

        if ($blocks === []) {
            throw new CrawlException($firstError ?? 'No readable text was found at that address.');
        }

        $text = implode("\n\n", $blocks);
        $cap = (int) config('lyro.crawl.max_chars', 300000);

        return [
            'title' => $title !== '' ? $title : (string) parse_url($startUrl, PHP_URL_HOST),
            'text' => mb_strlen($text) > $cap ? mb_substr($text, 0, $cap) : $text,
            'pages' => count($blocks),
        ];
    }

    /**
     * A single download (page or PDF) → [title, text, links].
     *
     * @param  array{url: string, status: int, type: string, body: string}  $page
     * @return array{0: string, 1: string, 2: array<int, string>}
     */
    public function read(array $page): array
    {
        if ($page['type'] === 'pdf') {
            $pdf = $this->pdf->extract($page['body']);

            return [$pdf['title'], $pdf['text'], []];
        }

        if ($page['type'] === 'text') {
            $text = trim(preg_replace("/[ \t]+/", ' ', mb_convert_encoding($page['body'], 'UTF-8', 'UTF-8')) ?? '');

            return ['', $text, []];
        }

        $html = $this->html->extract($page['body'], $page['url']);

        return [$html['title'], $html['text'], $html['links']];
    }

    private function shouldFollow(string $link, string $siteHost, RobotsRules $robots): bool
    {
        $host = $this->site((string) parse_url($link, PHP_URL_HOST));

        if ($host !== $siteHost) {
            return false; // other websites are never followed
        }

        $extension = strtolower(pathinfo((string) parse_url($link, PHP_URL_PATH), PATHINFO_EXTENSION));

        if ($extension !== '' && in_array($extension, self::SKIP_EXTENSIONS, true)) {
            return false;
        }

        return $robots->allows($link);
    }

    private function robotsFor(string $pageUrl): RobotsRules
    {
        $parts = parse_url($pageUrl);

        if (! isset($parts['scheme'], $parts['host'])) {
            return RobotsRules::allowAll();
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        try {
            $robots = $this->fetcher->get($origin.'/robots.txt', 512 * 1024);

            return $robots['type'] === 'pdf' ? RobotsRules::allowAll() : RobotsRules::parse($robots['body']);
        } catch (CrawlException) {
            return RobotsRules::allowAll(); // no robots.txt (or unreachable) = no restrictions
        }
    }

    /** "www.example.com" and "example.com" are the same website. */
    private function site(string $host): string
    {
        return preg_replace('/^www\./', '', strtolower($host)) ?? $host;
    }

    private function key(string $url): string
    {
        $parts = parse_url(trim($url));
        $host = $this->site((string) ($parts['host'] ?? ''));
        $path = rtrim((string) ($parts['path'] ?? '/'), '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $host.$path.$query;
    }
}
