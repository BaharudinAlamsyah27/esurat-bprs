<?php

namespace App\Policies;

use App\Models\IncomingLetter;
use App\Models\User;

class IncomingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->is_active;
    }

    public function viewContents(User $user, IncomingLetter $incomingLetter): bool
    {
        return $this->view($user, $incomingLetter);
    }

    public function viewAttachments(User $user, IncomingLetter $incomingLetter): bool
    {
        return $this->viewContents($user, $incomingLetter);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->is_active && ($user->isAdmin() || $incomingLetter->registered_by === $user->id);
    }

    public function delete(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, IncomingLetter $incomingLetter): bool
    {
        return false;
    }

    public function forceDelete(User $user, IncomingLetter $incomingLetter): bool
    {
        return false;
    }
}
