<?php

namespace App\Policies;

use App\Enums\UserLevel;
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
        return $user->isAdmin();
    }

    public function delete(User $user, User $record): bool
    {
        return $user->isAdmin() && $user->isNot($record)
            && ! $record->hasUsageHistory()
            && (! $record->isAdmin() || ! $record->is_active || User::query()->where('level', UserLevel::Admin->value)->where('is_active', true)->count() > 1);
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
