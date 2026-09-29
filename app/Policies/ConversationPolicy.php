<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    protected function sameWorkspace(User $user, Conversation $conversation): bool
    {
        return $conversation->workspace->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->sameWorkspace($user, $conversation);
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        // Anyone in the workspace can reply — assignment doesn't gate replying,
        // only who gets notified/credited (mirrors Tidio's "All agents" view).
        return $this->sameWorkspace($user, $conversation);
    }

    public function reassign(User $user, Conversation $conversation): bool
    {
        return $this->sameWorkspace($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        // Only the assigned operator or the workspace owner can delete a thread.
        return $conversation->assigned_operator_id === $user->id
            || $conversation->workspace->owner_id === $user->id;
    }
}
