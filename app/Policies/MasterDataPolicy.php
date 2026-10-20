<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.manage');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can('master.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('master.manage');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->can('master.manage');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->can('master.manage');
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->can('master.manage');
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->can('master.manage');
    }
}
