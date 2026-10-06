<?php

namespace App\Services\Widget;

use App\Models\Website;

/**
 * Decides which pages may embed a website's chat widget.
 *
 * Two layers:
 *  - frameAncestors(): the CSP value that makes the *browser* refuse to render the chat frame
 *    on any site other than the one registered in Settings > Installation.
 *  - allowsOrigin(): a server-side check on the Origin header of widget API calls.
 */
class WidgetOriginPolicy
{
    /** Valid DNS name / localhost / IPv4 — anything else is never put into a CSP header. */
    private const HOST_PATTERN = '/^(localhost|([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}|(\d{1,3}\.){3}\d{1,3})$/';

    public function frameAncestors(Website $website): string
    {
        if (config('widget.allow_any_origin')) {
            return '*';
        }

        $sources = ["'self'"];
        $domain = Website::normalizeDomain((string) $website->domain);

        if (preg_match(self::HOST_PATTERN, $domain) === 1) {
            $sources[] = "https://{$domain}";
            $sources[] = "https://*.{$domain}";

            if (! app()->isProduction()) {
                // Local development: plain http and any dev-server port.
                $sources[] = "http://{$domain}";
                $sources[] = "http://{$domain}:*";
                $sources[] = "http://*.{$domain}";
                $sources[] = "http://*.{$domain}:*";
            }
        }

        return implode(' ', $sources);
    }

    /**
     * @param  string|null  $origin   Raw Origin header (null/empty for same-origin GETs and non-browser clients).
     * @param  string  $appHost  Host this app is served from; the chat frame itself calls us from there.
     */
    public function allowsOrigin(Website $website, ?string $origin, string $appHost): bool
    {
        if (config('widget.allow_any_origin') || $origin === null || $origin === '') {
            return true;
        }

        $host = parse_url($origin, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false; // Origin "null" (sandboxed / file://)
        }

        return strcasecmp($host, $appHost) === 0 || $website->allowsHost($host);
    }
}
