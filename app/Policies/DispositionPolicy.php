<?php

namespace App\Policies;

use App\Models\Disposition;
use App\Models\User;

class DispositionPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(User $user, Disposition $disposition): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function print(User $user, Disposition $disposition): bool
    {
        return $user->is_active;
    }
}
