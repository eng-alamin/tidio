<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    /**
     * Shared helper: is this user an active member of the workspace at all?
     * Every other check below builds on this.
     */
    protected function isMember(User $user, Workspace $workspace): bool
    {
        return $workspace->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    protected function roleName(User $user, Workspace $workspace): ?string
    {
        $pivot = $workspace->users()->where('users.id', $user->id)->first()?->pivot;

        return $pivot ? optional($pivot->role_id ? \App\Models\Role::find($pivot->role_id) : null)->name : null;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id || $this->roleName($user, $workspace) === 'Admin';
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        // Only the owner can delete the whole workspace — not even an Admin operator.
        return $workspace->owner_id === $user->id;
    }

    public function manageTeam(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id || $this->roleName($user, $workspace) === 'Admin';
    }

    public function manageBilling(User $user, Workspace $workspace): bool
    {
        // Billing stays owner-only regardless of Admin role — matches Tidio's
        // own behaviour where only the account owner sees the Billing tab.
        return $workspace->owner_id === $user->id;
    }
}
