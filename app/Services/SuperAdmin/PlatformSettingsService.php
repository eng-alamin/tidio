<?php

namespace App\Services\SuperAdmin;

use App\Models\SuperAdmin;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Platform-wide configuration kept in the `system_settings` key/value table.
 * Reads are cached (the maintenance check runs on every tenant page load);
 * every write clears the cache and is audit-logged.
 */
class PlatformSettingsService
{
    public const CACHE_KEY = 'platform_settings';

    private const BOOL_KEYS = ['allow_impersonation', 'maintenance_mode'];

    private const INT_KEYS = ['trial_days'];

    /** @return array<string, string|int|bool> */
    public function defaults(): array
    {
        return [
            'platform_name' => 'Loop',
            'support_email' => (string) config('mail.from.address', 'support@example.com'),
            'default_timezone' => 'UTC',
            'trial_days' => 14,
            'allow_impersonation' => true,
            'maintenance_mode' => false,
            'maintenance_message' => 'We are doing some scheduled maintenance and will be back shortly.',
        ];
    }

    /** @return array<string, string|int|bool> every setting, typed, with defaults filled in */
    public function all(): array
    {
        $stored = Cache::remember(self::CACHE_KEY, 3600, fn () => SystemSetting::query()->pluck('value', 'key')->all());

        $out = $this->defaults();

        foreach ($out as $key => $default) {
            if (! array_key_exists($key, $stored) || $stored[$key] === null) {
                continue;
            }

            $out[$key] = match (true) {
                in_array($key, self::BOOL_KEYS, true) => $stored[$key] === '1',
                in_array($key, self::INT_KEYS, true) => (int) $stored[$key],
                default => (string) $stored[$key],
            };
        }

        return $out;
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function platformName(): string
    {
        return (string) $this->get('platform_name');
    }

    public function supportEmail(): string
    {
        return (string) $this->get('support_email');
    }

    public function maintenanceMode(): bool
    {
        return (bool) $this->get('maintenance_mode');
    }

    public function maintenanceMessage(): string
    {
        return (string) $this->get('maintenance_message');
    }

    public function impersonationAllowed(): bool
    {
        return (bool) $this->get('allow_impersonation');
    }

    public function trialDays(): int
    {
        return max(1, (int) $this->get('trial_days'));
    }

    public function defaultTimezone(): string
    {
        return (string) $this->get('default_timezone');
    }

    /**
     * Saves the given settings in one transaction. Unknown keys are ignored.
     *
     * @param  array<string, string|int|bool>  $data
     * @return list<string> the keys whose value actually changed
     *
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, array $data): array
    {
        $data = array_intersect_key($data, $this->defaults());
        $before = $this->all();

        $changed = array_values(array_filter(
            array_keys($data),
            fn (string $key) => $before[$key] !== $data[$key]
        ));

        if ($changed === []) {
            return [];
        }

        DB::beginTransaction();

        try {
            foreach ($changed as $key) {
                SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $this->serialize($data[$key])]);
            }

            activity('settings')
                ->causedBy($actor)
                ->withProperties([
                    'changed' => $changed,
                    'old' => array_intersect_key($before, array_flip($changed)),
                    'new' => array_intersect_key($data, array_flip($changed)),
                ])
                ->log('Platform settings updated');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        Cache::forget(self::CACHE_KEY);

        return $changed;
    }

    private function serialize(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
