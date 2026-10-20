<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function view(User $user, User $record): bool
    {
        return $user->can('user.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function update(User $user, User $record): bool
    {
        return $user->can('user.manage') || $user->is($record);
    }

    public function delete(User $user, User $record): bool
    {
        return $user->can('user.manage') && $user->isNot($record);
    }

    public function restore(User $user, User $record): bool
    {
        return $user->can('user.manage');
    }

    public function forceDelete(User $user, User $record): bool
    {
        return false;
    }

    public function updateSignature(User $user, User $record): bool
    {
        return $user->is($record) || $user->can('user.manage');
    }
}
