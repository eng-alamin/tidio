<?php

namespace App\Services\Knowledge;

use Closure;

/**
 * Server-side request forgery (SSRF) protection for the crawler.
 *
 * The crawler fetches addresses typed in by workspace owners, so without checks it could be
 * pointed at localhost, the cloud metadata service (169.254.169.254) or other machines on the
 * private network. check() accepts only http(s) URLs on the standard ports whose host resolves
 * ONLY to public addresses, and returns the IP that was checked so the fetcher can pin it
 * (otherwise a DNS answer could change between this check and the real request).
 */
class UrlGuard
{
    /** @var (Closure(string): array<int, string>)|null  Test hook: replaces real DNS. */
    private static ?Closure $resolver = null;

    /** @param  (Closure(string): array<int, string>)|null  $resolver */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * @return array{url: string, scheme: string, host: string, port: int, ip: string}
     *
     * @throws CrawlException
     */
    public function check(string $url): array
    {
        $url = trim($url);
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new CrawlException('That is not a valid web address.');
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new CrawlException('Only http:// and https:// addresses can be read.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new CrawlException('Addresses with a username or password are not allowed.');
        }

        $host = trim(strtolower(rtrim($parts['host'], '.')), '[]'); // IPv6 literals come wrapped in brackets
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $allowPrivate = (bool) config('lyro.crawl.allow_private', false);

        if (! $allowPrivate && ! in_array($port, [80, 443], true)) {
            throw new CrawlException('Only the standard web ports (80 and 443) can be read.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);

        if ($ips === []) {
            throw new CrawlException("The address \"{$host}\" could not be found.");
        }

        if (! $allowPrivate) {
            foreach ($ips as $ip) {
                if (! $this->isPublic($ip)) {
                    throw new CrawlException('That address points to a private or internal network, so it cannot be read.');
                }
            }
        }

        return ['url' => $url, 'scheme' => $scheme, 'host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }

    /** @return array<int, string> */
    private function resolve(string $host): array
    {
        if (self::$resolver) {
            return (self::$resolver)($host);
        }

        $ips = @gethostbynamel($host) ?: [];

        if (function_exists('dns_get_record')) {
            foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (! empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    public function isPublic(string $ip): bool
    {
        // IPv4-mapped IPv6 (::ffff:127.0.0.1) must be judged by the IPv4 address inside it.
        if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)) {
            $ip = $m[1];
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        // Carrier-grade NAT 100.64.0.0/10 is not covered by the PHP flags.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);

            if ($long !== false && $long >= ip2long('100.64.0.0') && $long <= ip2long('100.127.255.255')) {
                return false;
            }
        }

        return true;
    }
}
