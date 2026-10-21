<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $record): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $record): bool
    {
        return $user->isAdmin() || $user->is($record);
    }

    public function delete(User $user, User $record): bool
    {
        return $user->isAdmin() && $user->isNot($record)
            && (! $record->isAdmin() || User::query()->where('level', 'admin')->where('is_active', true)->count() > 1);
    }

    public function restore(User $user, User $record): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, User $record): bool
    {
        return false;
    }
}
