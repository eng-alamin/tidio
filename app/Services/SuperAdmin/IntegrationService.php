<?php

namespace App\Services\SuperAdmin;

use App\Models\Integration;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Changes to the public integrations catalogue made from the Super Admin panel.
 * The slug is part of public URLs, so it is set once on create and never changes.
 */
class IntegrationService
{
    /**
     * @param  array{name:string, slug:string, category:?string, logo:?string, description:?string, is_featured:bool}  $data
     *
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, array $data): Integration
    {
        return DB::transaction(function () use ($actor, $data) {
            $integration = Integration::create($data);

            activity('integration')
                ->causedBy($actor)
                ->performedOn($integration)
                ->withProperties(['name' => $integration->name, 'slug' => $integration->slug, 'category' => $integration->category])
                ->log('Integration created');

            return $integration;
        });
    }

    /**
     * @param  array{name:string, category:?string, logo:?string, description:?string, is_featured:bool}  $data  slug is ignored
     *
     * @throws RuntimeException when the integration no longer exists
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, int $id, array $data): Integration
    {
        return DB::transaction(function () use ($actor, $id, $data) {
            $integration = $this->locked($id);
            unset($data['slug']);

            $integration->fill($data);
            $changed = array_keys($integration->getDirty());

            if ($changed === []) {
                return $integration;
            }

            $integration->save();

            activity('integration')
                ->causedBy($actor)
                ->performedOn($integration)
                ->withProperties(['name' => $integration->name, 'slug' => $integration->slug, 'changed' => $changed])
                ->log('Integration updated');

            return $integration;
        });
    }

    /**
     * @throws RuntimeException when the integration no longer exists
     * @throws Throwable
     */
    public function setFeatured(SuperAdmin $actor, int $id, bool $featured): Integration
    {
        return DB::transaction(function () use ($actor, $id, $featured) {
            $integration = $this->locked($id);

            if ($integration->is_featured === $featured) {
                return $integration;
            }

            $integration->update(['is_featured' => $featured]);

            activity('integration')
                ->causedBy($actor)
                ->performedOn($integration)
                ->withProperties(['name' => $integration->name, 'is_featured' => $featured])
                ->log($featured ? 'Integration featured' : 'Integration unfeatured');

            return $integration;
        });
    }

    /**
     * Soft delete. The slug stays reserved.
     *
     * @throws RuntimeException when the integration no longer exists
     * @throws Throwable
     */
    public function delete(SuperAdmin $actor, int $id): Integration
    {
        return DB::transaction(function () use ($actor, $id) {
            $integration = $this->locked($id);
            $integration->delete();

            activity('integration')
                ->causedBy($actor)
                ->performedOn($integration)
                ->withProperties(['name' => $integration->name, 'slug' => $integration->slug])
                ->log('Integration deleted');

            return $integration;
        });
    }

    private function locked(int $id): Integration
    {
        $integration = Integration::query()->lockForUpdate()->find($id);

        if (! $integration) {
            throw new RuntimeException('That integration no longer exists.');
        }

        return $integration;
    }
}
