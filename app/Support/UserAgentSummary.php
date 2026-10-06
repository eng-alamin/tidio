<?php

namespace App\Support;

/**
 * Tiny User-Agent summariser for the visitor card ("Chrome 118", "Windows").
 * Deliberately simple; it only needs to be good enough for a label in the Inbox.
 */
class UserAgentSummary
{
    public static function browser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $patterns = [
            'Edge' => '/Edg(?:e|A|iOS)?\/(\d+)/',
            'Opera' => '/(?:OPR|Opera)\/(\d+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/(\d+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/(\d+)/',
            'Safari' => '/Version\/(\d+).*Safari\//',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $userAgent, $m)) {
                return $name.' '.$m[1];
            }
        }

        return 'Unknown';
    }

    public static function os(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/Windows/i', $userAgent) => 'Windows',
            (bool) preg_match('/Android/i', $userAgent) => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/i', $userAgent) => 'iOS',
            (bool) preg_match('/Mac OS X|Macintosh/i', $userAgent) => 'macOS',
            (bool) preg_match('/Linux|X11/i', $userAgent) => 'Linux',
            default => null,
        };
    }
}
