<?php

namespace App\Services\SuperAdmin;

use App\Models\Role;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * All write operations on platform users made from the Super Admin panel.
 * Multi-table changes run in one transaction and every change is audit-logged.
 */
class UserService
{
    /**
     * Creates a user and attaches them to a workspace with the given role.
     *
     * @param  array{name:string, email:string, password:string, workspace_id:int, role_id:int}  $data
     *
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, array $data): User
    {
        $workspace = Workspace::findOrFail($data['workspace_id']);
        $role = Role::where('workspace_id', $workspace->id)->findOrFail($data['role_id']);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model cast
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $workspace->users()->attach($user->id, [
                'role_id' => $role->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            activity('user')
                ->causedBy($actor)
                ->performedOn($user)
                ->withProperties(['workspace' => $workspace->name, 'role' => $role->name])
                ->log('User created');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }

    /**
     * @param  array{name:string, email:string, password?:?string}  $data
     *
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, User $user, array $data): User
    {
        $old = ['name' => $user->name, 'email' => $user->email];

        DB::beginTransaction();

        try {
            $attributes = ['name' => $data['name'], 'email' => $data['email']];

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password']; // hashed by the model cast
            }

            $user->update($attributes);

            activity('user')
                ->causedBy($actor)
                ->performedOn($user)
                ->withProperties([
                    'old' => $old,
                    'new' => ['name' => $user->name, 'email' => $user->email],
                    'password_changed' => ! empty($data['password']),
                ])
                ->log('User updated');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }

    /**
     * @throws Throwable
     */
    public function setDisabled(SuperAdmin $actor, User $user, bool $disabled): void
    {
        DB::beginTransaction();

        try {
            $user->update([
                'is_disabled' => $disabled,
                'disabled_at' => $disabled ? now() : null,
            ]);

            activity('user')
                ->causedBy($actor)
                ->performedOn($user)
                ->log($disabled ? 'User disabled' : 'User enabled');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Soft-deletes a user. Refuses when the user still owns a workspace, because
     * that would orphan the tenant — delete or hand over the tenant first.
     *
     * @throws RuntimeException when the user owns a workspace
     * @throws Throwable
     */
    public function delete(SuperAdmin $actor, User $user): void
    {
        $owned = $user->ownedWorkspaces()->pluck('name');

        if ($owned->isNotEmpty()) {
            throw new RuntimeException('Owns tenant(s): '.$owned->implode(', '));
        }

        DB::beginTransaction();

        try {
            // Free their seat in every workspace, then soft-delete the account.
            $user->workspaces()->detach();
            $user->delete();

            activity('user')
                ->causedBy($actor)
                ->performedOn($user)
                ->withProperties(['email' => $user->email])
                ->log('User deleted');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
