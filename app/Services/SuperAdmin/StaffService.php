<?php

namespace App\Services\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\AdminNote;
use App\Models\ImpersonationLog;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Platform staff accounts managed from the Super Admin panel.
 *
 * Guard rails live here, not only in the UI: you cannot change your own role or delete
 * yourself, and the platform can never be left without a super admin.
 */
class StaffService
{
    /**
     * @param  array{name:string, email:string, role:string, password:string}  $data
     *
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, array $data): SuperAdmin
    {
        return DB::transaction(function () use ($actor, $data) {
            $staff = SuperAdmin::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => $data['password'], // hashed by the model cast
            ]);

            activity('staff')
                ->causedBy($actor)
                ->performedOn($staff)
                ->withProperties(['name' => $staff->name, 'email' => $staff->email, 'role' => $staff->role->value])
                ->log('Staff account created');

            return $staff;
        });
    }

    /**
     * @param  array{name:string, email:string, role:string}  $data
     *
     * @throws RuntimeException on a blocked role change or a missing account
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, int $id, array $data): SuperAdmin
    {
        return DB::transaction(function () use ($actor, $id, $data) {
            $staff = $this->locked($id);
            $newRole = SuperAdminRole::from($data['role']);
            $oldRole = $staff->role;

            if ($newRole !== $oldRole) {
                if ($staff->id === $actor->id) {
                    throw new RuntimeException("You can't change your own role. Ask another super admin.");
                }
                if ($oldRole === SuperAdminRole::SuperAdmin) {
                    $this->assertAnotherSuperAdmin($staff->id);
                }
            }

            $staff->fill(['name' => $data['name'], 'email' => $data['email'], 'role' => $newRole]);
            $changed = array_keys($staff->getDirty());

            if ($changed === []) {
                return $staff;
            }

            $staff->save();

            activity('staff')
                ->causedBy($actor)
                ->performedOn($staff)
                ->withProperties([
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'changed' => $changed,
                    'role_from' => $oldRole->value,
                    'role_to' => $newRole->value,
                ])
                ->log('Staff account updated');

            return $staff;
        });
    }

    /**
     * Sets a new password and signs the person out of "remember me" sessions.
     *
     * @throws RuntimeException when the account no longer exists
     * @throws Throwable
     */
    public function resetPassword(SuperAdmin $actor, int $id, string $password): SuperAdmin
    {
        return DB::transaction(function () use ($actor, $id, $password) {
            $staff = $this->locked($id);

            $staff->forceFill([
                'password' => $password, // hashed by the model cast
                'remember_token' => Str::random(60),
            ])->save();

            activity('staff')
                ->causedBy($actor)
                ->performedOn($staff)
                ->withProperties(['name' => $staff->name, 'email' => $staff->email])
                ->log('Staff password reset');

            return $staff;
        });
    }

    /**
     * @throws RuntimeException when deleting yourself, the last super admin, or someone with impersonation history
     * @throws Throwable
     */
    public function delete(SuperAdmin $actor, int $id): SuperAdmin
    {
        return DB::transaction(function () use ($actor, $id) {
            $staff = $this->locked($id);

            if ($staff->id === $actor->id) {
                throw new RuntimeException("You can't delete your own account.");
            }
            if ($staff->role === SuperAdminRole::SuperAdmin) {
                $this->assertAnotherSuperAdmin($staff->id);
            }
            // Impersonation history must stay attributable, so the database refuses the delete.
            if (ImpersonationLog::query()->where('super_admin_id', $staff->id)->exists()) {
                throw new RuntimeException('This person has impersonation history, which must stay on record. Change their role to Support Staff and reset their password instead.');
            }

            $notes = AdminNote::query()->where('super_admin_id', $staff->id)->count();
            $staff->delete(); // their notes are removed by the foreign key cascade

            activity('staff')
                ->causedBy($actor)
                ->performedOn($staff)
                ->withProperties(['name' => $staff->name, 'email' => $staff->email, 'role' => $staff->role->value, 'notes_removed' => $notes])
                ->log('Staff account deleted');

            return $staff;
        });
    }

    private function locked(int $id): SuperAdmin
    {
        $staff = SuperAdmin::query()->lockForUpdate()->find($id);

        if (! $staff) {
            throw new RuntimeException('That account no longer exists.');
        }

        return $staff;
    }

    /** @throws RuntimeException when no other super admin would remain */
    private function assertAnotherSuperAdmin(int $exceptId): void
    {
        $others = SuperAdmin::query()
            ->where('role', SuperAdminRole::SuperAdmin->value)
            ->where('id', '!=', $exceptId)
            ->lockForUpdate()
            ->count();

        if ($others === 0) {
            throw new RuntimeException('There must always be at least one super admin.');
        }
    }
}
