<?php

namespace App\Policies;

use App\Models\DispositionRecipient;
use App\Models\User;

class DispositionRecipientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, DispositionRecipient $recipient): bool
    {
        return $user->is_active && ($user->isAdmin() || $recipient->user_id === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(User $user, DispositionRecipient $recipient): bool
    {
        return $user->is_active && ($user->isAdmin() || $recipient->user_id === $user->getKey());
    }

    public function delete(User $user, DispositionRecipient $recipient): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
