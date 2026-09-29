<?php

namespace App\Policies;

use App\Models\Macro;
use App\Models\User;

class MacroPolicy
{
    public function view(User $user, Macro $macro): bool
    {
        return $macro->workspace->users()->where('users.id', $user->id)->wherePivot('status', 'active')->exists();
    }

    public function update(User $user, Macro $macro): bool
    {
        // Anyone can use a macro; only its author or the workspace owner can edit it.
        return $macro->created_by === $user->id || $macro->workspace->owner_id === $user->id;
    }

    public function delete(User $user, Macro $macro): bool
    {
        return $this->update($user, $macro);
    }
}
