<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    protected function sameWorkspace(User $user, Contact $contact): bool
    {
        return $contact->workspace->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function view(User $user, Contact $contact): bool
    {
        return $this->sameWorkspace($user, $contact);
    }

    public function update(User $user, Contact $contact): bool
    {
        return $this->sameWorkspace($user, $contact);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $this->sameWorkspace($user, $contact);
    }
}
