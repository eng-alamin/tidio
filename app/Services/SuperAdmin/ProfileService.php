<?php

namespace App\Services\SuperAdmin;

use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Changes a platform staff member makes to their own account. */
class ProfileService
{
    /**
     * @param  array{name:string, email:string, phone:?string, timezone:?string}  $data
     * @return list<string> the fields that changed
     *
     * @throws Throwable
     */
    public function updateProfile(SuperAdmin $admin, array $data): array
    {
        return DB::transaction(function () use ($admin, $data) {
            $admin->fill($data);
            $changed = array_keys($admin->getDirty());

            if ($changed === []) {
                return [];
            }

            $admin->save();

            activity('super-admin')
                ->causedBy($admin)
                ->performedOn($admin)
                ->withProperties(['changed' => $changed])
                ->log('Profile updated');

            return $changed;
        });
    }

    /**
     * The caller must already have verified the current password.
     *
     * @throws Throwable
     */
    public function changePassword(SuperAdmin $admin, string $newPassword): void
    {
        DB::transaction(function () use ($admin, $newPassword) {
            $admin->password = $newPassword; // hashed by the model cast
            $admin->save();

            activity('super-admin')
                ->causedBy($admin)
                ->performedOn($admin)
                ->log('Password changed');
        });
    }
}
