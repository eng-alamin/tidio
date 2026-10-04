<?php

namespace App\Services\SuperAdmin;

use App\Models\SuperAdmin;
use App\Models\Webhook;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * API key and webhook changes made from the Super Admin panel.
 *
 * Staff never see a full key or a webhook secret: they can only rotate a key, or pause,
 * resume and delete a webhook. Audit entries record the last four characters of a key at
 * most, never the key itself.
 */
class ApiAccessService
{
    public const KEY_PREFIX = 'loop_live_';

    /** Masks a workspace API key for display, keeping the prefix and the last four characters. */
    public static function maskKey(?string $key): string
    {
        if ($key === null || $key === '') {
            return 'No key';
        }

        $prefix = str_starts_with($key, self::KEY_PREFIX) ? self::KEY_PREFIX : '';

        return $prefix.'••••••••'.substr($key, -4);
    }

    /**
     * Replaces a tenant's API key. The old key stops working immediately.
     *
     * @throws RuntimeException when the tenant no longer exists
     * @throws Throwable
     */
    public function rotateKey(SuperAdmin $actor, int $workspaceId): Workspace
    {
        return DB::transaction(function () use ($actor, $workspaceId) {
            $workspace = Workspace::query()->lockForUpdate()->find($workspaceId);

            if (! $workspace) {
                throw new RuntimeException('That tenant no longer exists.');
            }

            $oldTail = $workspace->api_key ? substr($workspace->api_key, -4) : null;
            $newKey = self::KEY_PREFIX.Str::random(32);

            // api_key is deliberately not mass-assignable on every code path, so set it explicitly.
            $workspace->forceFill(['api_key' => $newKey])->save();

            activity('api_key')
                ->causedBy($actor)
                ->performedOn($workspace)
                ->withProperties([
                    'workspace_id' => $workspace->id,
                    'workspace' => $workspace->name,
                    'old_key_ends_with' => $oldTail,
                    'new_key_ends_with' => substr($newKey, -4),
                ])
                ->log('Workspace API key rotated');

            return $workspace;
        });
    }

    /**
     * Pauses or resumes a webhook. A paused webhook keeps its URL, events and secret.
     *
     * @throws RuntimeException when the webhook no longer exists
     * @throws Throwable
     */
    public function setWebhookActive(SuperAdmin $actor, int $webhookId, bool $active): Webhook
    {
        return DB::transaction(function () use ($actor, $webhookId, $active) {
            $webhook = $this->lockedWebhook($webhookId);

            if ($webhook->is_active === $active) {
                return $webhook;
            }

            $webhook->update(['is_active' => $active]);

            activity('webhook')
                ->causedBy($actor)
                ->performedOn($webhook)
                ->withProperties($this->webhookProperties($webhook) + ['is_active' => $active])
                ->log($active ? 'Webhook resumed' : 'Webhook paused');

            return $webhook;
        });
    }

    /**
     * Soft deletes a webhook. The tenant can add a new one from its own settings.
     *
     * @throws RuntimeException when the webhook no longer exists
     * @throws Throwable
     */
    public function deleteWebhook(SuperAdmin $actor, int $webhookId): Webhook
    {
        return DB::transaction(function () use ($actor, $webhookId) {
            $webhook = $this->lockedWebhook($webhookId);

            $webhook->delete();

            activity('webhook')
                ->causedBy($actor)
                ->performedOn($webhook)
                ->withProperties($this->webhookProperties($webhook))
                ->log('Webhook deleted');

            return $webhook;
        });
    }

    /**
     * Host and path only: query strings and credentials in a URL can carry secrets.
     */
    public static function displayUrl(?string $url): string
    {
        $parts = $url ? parse_url($url) : false;

        if (! is_array($parts) || empty($parts['host'])) {
            return '(invalid URL)';
        }

        return ($parts['scheme'] ?? 'https').'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '');
    }

    private function lockedWebhook(int $id): Webhook
    {
        $webhook = Webhook::query()->with(['workspace' => fn ($q) => $q->withTrashed()])->lockForUpdate()->find($id);

        if (! $webhook) {
            throw new RuntimeException('That webhook no longer exists.');
        }

        return $webhook;
    }

    /** @return array<string, mixed> */
    private function webhookProperties(Webhook $webhook): array
    {
        return [
            'workspace_id' => $webhook->workspace_id,
            'workspace' => $webhook->workspace?->name,
            'endpoint' => self::displayUrl($webhook->url),
            'events' => $webhook->events,
        ];
    }
}
