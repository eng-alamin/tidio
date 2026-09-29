<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;

class WebhookPolicy
{
    // Webhooks expose the workspace's secret and can leak data externally,
    // so — unlike Contact/Macro — only the owner or an Admin may touch them,
    // not every operator.
    protected function isAdminOrOwner(User $user, Webhook $webhook): bool
    {
        if ($webhook->workspace->owner_id === $user->id) {
            return true;
        }

        $pivot = $webhook->workspace->users()->where('users.id', $user->id)->first()?->pivot;
        $role = $pivot && $pivot->role_id ? \App\Models\Role::find($pivot->role_id) : null;

        return $role?->name === 'Admin';
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $this->isAdminOrOwner($user, $webhook);
    }

    public function create(User $user, Webhook $webhook): bool
    {
        return $this->isAdminOrOwner($user, $webhook);
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $this->isAdminOrOwner($user, $webhook);
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $this->isAdminOrOwner($user, $webhook);
    }
}
