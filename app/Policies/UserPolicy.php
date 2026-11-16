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
        return $user->isAdmin() && ($user->isSuperAdmin() || ! $record->isSuperAdmin());
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $record): bool
    {
        return $user->isAdmin() && ($user->isSuperAdmin() || ! $record->isSuperAdmin());
    }

    public function delete(User $user, User $record): bool
    {
        return $user->isAdmin()
            && ($user->isSuperAdmin() || ! $record->isSuperAdmin())
            && $user->isNot($record)
            && ! $record->hasUsageHistory()
            && (! $record->isSuperAdmin() || ! $record->is_active || User::query()->where('level', UserLevel::SuperAdmin->value)->where('is_active', true)->count() > 1);
    }

    public function restore(User $user, User $record): bool
    {
        return $user->isAdmin() && ($user->isSuperAdmin() || ! $record->isSuperAdmin());
    }

    public function forceDelete(User $user, User $record): bool
    {
        return false;
    }
}
