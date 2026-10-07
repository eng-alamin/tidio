<?php

namespace App\Services\Widget;

use App\Support\Countries;
use Illuminate\Http\Request;
use Throwable;

/**
 * Works out which country a widget visitor is in, WITHOUT calling any third-party service
 * (a visitor's IP never leaves your server).
 *
 * Order:
 *  1. A country header added by your CDN / proxy (Cloudflare `CF-IPCountry`, CloudFront
 *     `CloudFront-Viewer-Country`, Vercel `X-Vercel-IP-Country`, ...). Configure the list in
 *     `config('widget.geo.headers')`; set it to [] if the app is NOT behind such a proxy, because
 *     a visitor could otherwise send the header themselves.
 *  2. An optional MaxMind GeoLite2/GeoIP2 Country database (`widget.geo.database`) read through the
 *     `geoip2/geoip2` package, if both are installed.
 *  3. `widget.geo.default_country` (handy on localhost), otherwise null = unknown.
 */
class VisitorCountryResolver
{
    /** Cloudflare / CDN placeholders that mean "unknown". */
    private const UNKNOWN = ['XX', 'T1', 'ZZ', 'A1', 'A2'];

    public function resolve(Request $request): ?string
    {
        if (! config('widget.geo.enabled', true)) {
            return null;
        }

        foreach ((array) config('widget.geo.headers', []) as $header) {
            $code = strtoupper(trim((string) $request->headers->get((string) $header)));

            if ($code !== '' && ! in_array($code, self::UNKNOWN, true) && Countries::has($code)) {
                return $code;
            }
        }

        $fromDatabase = $this->fromDatabase($request->ip());

        if ($fromDatabase !== null) {
            return $fromDatabase;
        }

        $default = strtoupper(trim((string) config('widget.geo.default_country')));

        return Countries::has($default) ? $default : null;
    }

    private function fromDatabase(?string $ip): ?string
    {
        $path = config('widget.geo.database');

        if (! $ip || ! is_string($path) || $path === '' || ! is_file($path)
            || ! class_exists(\GeoIp2\Database\Reader::class)) {
            return null;
        }

        try {
            $reader = new \GeoIp2\Database\Reader($path);
            $code = strtoupper((string) $reader->country($ip)->country->isoCode);

            return Countries::has($code) ? $code : null;
        } catch (Throwable) {
            return null; // private/reserved IP, corrupt database, ... — country just stays unknown
        }
    }
}
