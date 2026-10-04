<?php

namespace App\Services\SuperAdmin;

use App\Models\FeatureFlag;
use App\Models\SuperAdmin;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Feature flag changes made from the Super Admin panel.
 *
 * A flag is identified by its key. The row with workspace_id = null is the global default;
 * rows with a workspace_id are per-tenant overrides. The table's unique index does not stop
 * duplicate global rows (NULLs never collide), so global uniqueness is enforced here.
 */
class FeatureFlagService
{
    /**
     * @throws RuntimeException when the key already exists
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, string $key): FeatureFlag
    {
        return DB::transaction(function () use ($actor, $key) {
            $exists = FeatureFlag::query()->whereNull('workspace_id')->where('key', $key)->lockForUpdate()->exists();

            if ($exists) {
                throw new RuntimeException("A flag named {$key} already exists.");
            }

            $flag = FeatureFlag::create(['workspace_id' => null, 'key' => $key, 'is_enabled' => false]);

            activity('feature_flag')
                ->causedBy($actor)
                ->performedOn($flag)
                ->withProperties(['key' => $key])
                ->log('Feature flag created');

            return $flag;
        });
    }

    /** @throws Throwable */
    public function setGlobal(SuperAdmin $actor, FeatureFlag $flag, bool $enabled): void
    {
        DB::transaction(function () use ($actor, $flag, $enabled) {
            $flag = $this->globalFlag($flag->id);

            if ($flag->is_enabled === $enabled) {
                return;
            }

            $flag->update(['is_enabled' => $enabled]);

            activity('feature_flag')
                ->causedBy($actor)
                ->performedOn($flag)
                ->withProperties(['key' => $flag->key, 'is_enabled' => $enabled])
                ->log($enabled ? 'Feature flag turned on' : 'Feature flag turned off');
        });
    }

    /**
     * Adds or updates a tenant override.
     *
     * @throws RuntimeException when the flag or tenant no longer exists
     * @throws Throwable
     */
    public function setOverride(SuperAdmin $actor, FeatureFlag $flag, int $workspaceId, bool $enabled): void
    {
        DB::transaction(function () use ($actor, $flag, $workspaceId, $enabled) {
            $flag = $this->globalFlag($flag->id);
            $workspace = Workspace::query()->find($workspaceId);

            if (! $workspace) {
                throw new RuntimeException('That tenant no longer exists.');
            }

            $override = FeatureFlag::query()->where('workspace_id', $workspace->id)->where('key', $flag->key)->lockForUpdate()->first();
            $from = $override?->is_enabled;

            if ($override) {
                if ($override->is_enabled === $enabled) {
                    return;
                }
                $override->update(['is_enabled' => $enabled]);
            } else {
                $override = FeatureFlag::create(['workspace_id' => $workspace->id, 'key' => $flag->key, 'is_enabled' => $enabled]);
            }

            activity('feature_flag')
                ->causedBy($actor)
                ->performedOn($override)
                ->withProperties([
                    'key' => $flag->key,
                    'workspace_id' => $workspace->id,
                    'workspace' => $workspace->name,
                    'from' => $from,
                    'to' => $enabled,
                ])
                ->log('Feature flag tenant override set');
        });
    }

    /** @throws Throwable */
    public function removeOverride(SuperAdmin $actor, FeatureFlag $override): void
    {
        DB::transaction(function () use ($actor, $override) {
            $row = FeatureFlag::query()->whereNotNull('workspace_id')->find($override->id);

            if (! $row) {
                return; // already gone
            }

            $row->delete();

            activity('feature_flag')
                ->causedBy($actor)
                ->performedOn($row)
                ->withProperties(['key' => $row->key, 'workspace_id' => $row->workspace_id])
                ->log('Feature flag tenant override removed');
        });
    }

    /** Deletes the flag and every tenant override for its key. Code checking it falls back to off. */
    public function delete(SuperAdmin $actor, FeatureFlag $flag): void
    {
        DB::transaction(function () use ($actor, $flag) {
            $flag = $this->globalFlag($flag->id);

            $overrides = FeatureFlag::query()->whereNotNull('workspace_id')->where('key', $flag->key)->delete();
            $flag->delete();

            activity('feature_flag')
                ->causedBy($actor)
                ->performedOn($flag)
                ->withProperties(['key' => $flag->key, 'overrides_removed' => $overrides])
                ->log('Feature flag deleted');
        });
    }

    private function globalFlag(int $id): FeatureFlag
    {
        $flag = FeatureFlag::query()->whereNull('workspace_id')->lockForUpdate()->find($id);

        if (! $flag) {
            throw new RuntimeException('That flag no longer exists.');
        }

        return $flag;
    }
}
