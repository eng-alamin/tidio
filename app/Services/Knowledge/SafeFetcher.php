<?php

namespace App\Services\Knowledge;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Downloads one web address safely: SSRF-checked at every redirect hop, DNS pinned to the checked
 * IP, hard limits on time and size, no cookies, no credentials.
 */
class SafeFetcher
{
    public function __construct(private readonly UrlGuard $guard)
    {
    }

    /**
     * @return array{url: string, status: int, type: string, body: string}  type: html | pdf | text
     *
     * @throws CrawlException
     */
    public function get(string $url, ?int $maxBytes = null): array
    {
        $maxBytes ??= (int) config('lyro.crawl.max_bytes', 10 * 1024 * 1024);
        $hops = (int) config('lyro.crawl.max_redirects', 3);
        $current = $url;

        for ($i = 0; $i <= $hops; $i++) {
            $target = $this->guard->check($current);
            $response = $this->request($target);
            $status = $response->status();

            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                $location = trim((string) $response->header('Location'));
                $next = $location !== '' ? HtmlTextExtractor::resolve($current, $location) : null;

                if ($next === null) {
                    throw new CrawlException('The page redirected somewhere that could not be followed.');
                }

                $current = $next;

                continue;
            }

            if ($status === 404 || $status === 410) {
                throw new CrawlException('That page was not found (HTTP '.$status.').');
            }

            if ($status === 401 || $status === 403) {
                throw new CrawlException('That page is not publicly accessible (HTTP '.$status.').');
            }

            if ($status >= 400) {
                throw new CrawlException('The website answered with an error (HTTP '.$status.').');
            }

            $body = $this->readBody($response, $maxBytes);

            return ['url' => $current, 'status' => $status, 'type' => $this->classify($response, $body), 'body' => $body];
        }

        throw new CrawlException('The address redirects too many times.');
    }

    /** @param  array{url: string, scheme: string, host: string, port: int, ip: string}  $target */
    private function request(array $target): Response
    {
        $options = ['allow_redirects' => false, 'stream' => true];

        // Connect to the IP we just validated instead of resolving the name a second time.
        if (defined('CURLOPT_RESOLVE') && ! filter_var($target['host'], FILTER_VALIDATE_IP)) {
            $ip = str_contains($target['ip'], ':') ? '['.$target['ip'].']' : $target['ip'];
            $options['curl'] = [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$ip}"]];
        }

        try {
            return Http::timeout((int) config('lyro.crawl.timeout', 15))
                ->connectTimeout(8)
                ->withHeaders([
                    'User-Agent' => (string) config('lyro.crawl.user_agent'),
                    'Accept' => 'text/html,application/xhtml+xml,application/pdf;q=0.9,text/plain;q=0.8,*/*;q=0.1',
                ])
                ->withOptions($options)
                ->get($target['url']);
        } catch (ConnectionException) {
            throw new CrawlException('Could not connect to that website (it may be down or blocking us).');
        }
    }

    private function readBody(Response $response, int $maxBytes): string
    {
        $limit = $maxBytes >= 1048576 ? round($maxBytes / 1048576).' MB' : max(1, round($maxBytes / 1024)).' KB';
        $tooLarge = new CrawlException("That file is too large (the limit is {$limit}).");

        $declared = (int) $response->header('Content-Length');

        if ($declared > $maxBytes) {
            throw $tooLarge;
        }

        $stream = $response->toPsrResponse()->getBody();
        $data = '';

        while (! $stream->eof()) {
            $data .= $stream->read(65536);

            if (strlen($data) > $maxBytes) {
                throw $tooLarge;
            }
        }

        return $data;
    }

    private function classify(Response $response, string $body): string
    {
        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

        if ($type === 'application/pdf' || str_starts_with($body, '%PDF-')) {
            return 'pdf';
        }

        if (in_array($type, ['text/html', 'application/xhtml+xml', ''], true)) {
            return 'html';
        }

        if (str_starts_with($type, 'text/')) {
            return 'text';
        }

        throw new CrawlException('That address is not a web page or PDF ('.($type ?: 'unknown type').').');
    }
}
