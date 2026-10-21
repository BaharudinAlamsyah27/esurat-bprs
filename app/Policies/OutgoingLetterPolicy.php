<?php

namespace App\Policies;

use App\Models\OutgoingLetter;
use App\Models\User;

class OutgoingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, OutgoingLetter $letter): bool
    {
        return $user->is_active;
    }

    public function viewAttachments(User $user, OutgoingLetter $letter): bool
    {
        return $this->view($user, $letter);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, OutgoingLetter $letter): bool
    {
        return $user->is_active && ($user->isAdmin() || $letter->created_by === $user->id);
    }

    public function delete(User $user, OutgoingLetter $letter): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function restore(User $user, OutgoingLetter $letter): bool
    {
        return false;
    }

    public function forceDelete(User $user, OutgoingLetter $letter): bool
    {
        return false;
    }
}
