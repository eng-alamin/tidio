<?php

namespace App\Services\Knowledge;

/**
 * Minimal robots.txt reader (Allow / Disallow with * and $, longest match wins). Only used to
 * decide which LINKED pages the crawler follows; a page the owner types in is always read.
 */
class RobotsRules
{
    /** @param  array<int, array{allow: bool, pattern: string}>  $rules */
    private function __construct(private readonly array $rules)
    {
    }

    public static function allowAll(): self
    {
        return new self([]);
    }

    public static function parse(string $robotsTxt, string $agent = 'lyrobot'): self
    {
        $agent = strtolower($agent);
        $groups = [];          // each: ['agents' => [], 'rules' => []]
        $current = null;
        $lastWasAgent = false;

        foreach (preg_split('/\r\n|\r|\n/', $robotsTxt) ?: [] as $line) {
            $line = trim(preg_replace('/#.*/', '', $line) ?? '');

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if (! $lastWasAgent) {
                    $groups[] = ['agents' => [], 'rules' => []];
                    $current = array_key_last($groups);
                }

                $groups[$current]['agents'][] = strtolower($value);
                $lastWasAgent = true;

                continue;
            }

            $lastWasAgent = false;

            if ($current !== null && in_array($field, ['allow', 'disallow'], true) && $value !== '') {
                $groups[$current]['rules'][] = ['allow' => $field === 'allow', 'pattern' => $value];
            }
        }

        // The most specific group naming us wins; otherwise the "*" group.
        $specific = $star = null;

        foreach ($groups as $group) {
            foreach ($group['agents'] as $name) {
                if ($name !== '*' && $name !== '' && str_contains($agent, $name)) {
                    $specific = $group['rules'];
                } elseif ($name === '*') {
                    $star = $group['rules'];
                }
            }
        }

        return new self($specific ?? $star ?? []);
    }

    public function allows(string $url): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
        $query = parse_url($url, PHP_URL_QUERY);
        $target = ($path === '' ? '/' : $path).($query ? '?'.$query : '');

        $best = null;
        $bestLength = -1;

        foreach ($this->rules as $rule) {
            if ($this->matches($rule['pattern'], $target) && strlen($rule['pattern']) >= $bestLength) {
                // Longest pattern wins; on a tie, Allow beats Disallow.
                if (strlen($rule['pattern']) > $bestLength || $rule['allow']) {
                    $best = $rule['allow'];
                    $bestLength = strlen($rule['pattern']);
                }
            }
        }

        return $best ?? true;
    }

    private function matches(string $pattern, string $target): bool
    {
        $anchored = str_ends_with($pattern, '$');
        $regex = preg_quote($anchored ? substr($pattern, 0, -1) : $pattern, '#');
        $regex = str_replace('\*', '.*', $regex);

        return (bool) preg_match('#^'.$regex.($anchored ? '$' : '').'#', $target);
    }
}
