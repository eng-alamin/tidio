<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Website extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['workspace_id', 'domain', 'widget_key', 'installed_at'];

    protected $casts = ['installed_at' => 'datetime'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function widgetSetting(): HasOne
    {
        return $this->hasOne(WidgetSetting::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WidgetTranslation::class);
    }

    /**
     * Reduces whatever the user typed ("https://www.Example.com:8080/pricing") to the bare
     * host we store and compare against ("example.com").
     */
    public static function normalizeDomain(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^[a-z][a-z0-9+.\-]*://#', '', $value) ?? '';
        $value = preg_split('#[/?\#]#', $value, 2)[0] ?? '';
        $value = preg_replace('/:\d+$/', '', $value) ?? '';
        $value = preg_replace('/^www\./', '', $value) ?? '';

        return rtrim($value, '.');
    }

    /** True when $host is this website's domain or any subdomain of it. */
    public function allowsHost(?string $host): bool
    {
        $host = strtolower(rtrim((string) $host, '.'));
        $domain = self::normalizeDomain((string) $this->domain);

        if ($host === '' || $domain === '') {
            return false;
        }

        return $host === $domain || str_ends_with($host, '.'.$domain);
    }

    /** Flips the Dashboard's "Chat widget: Installed" state, once. */
    public function markInstalled(): void
    {
        if ($this->installed_at === null) {
            $this->forceFill(['installed_at' => now()])->save();
        }
    }

    /**
     * Marks the website installed when $pageUrl is a page on its registered domain.
     * Pages inside the app panel itself (/app/..., e.g. the widget preview) never count.
     */
    public function markInstalledFrom(?string $pageUrl): void
    {
        if ($this->installed_at !== null || ! $pageUrl) {
            return;
        }

        $parts = parse_url($pageUrl);
        $host = $parts['host'] ?? null;
        $path = $parts['path'] ?? '/';

        if ($this->allowsHost($host) && ! str_starts_with($path, '/app/')) {
            $this->markInstalled();
        }
    }
}
