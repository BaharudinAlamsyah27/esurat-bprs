<?php

namespace App\Policies;

use App\Models\CooperationAgreement;
use App\Models\User;

class CooperationAgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, CooperationAgreement $agreement): bool
    {
        return $user->is_active;
    }

    public function viewAttachments(User $user, CooperationAgreement $agreement): bool
    {
        return $this->view($user, $agreement);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, CooperationAgreement $agreement): bool
    {
        return $user->is_active && ($user->isAdmin() || $agreement->created_by === $user->id);
    }

    public function delete(User $user, CooperationAgreement $agreement): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function restore(User $user, CooperationAgreement $agreement): bool
    {
        return false;
    }

    public function forceDelete(User $user, CooperationAgreement $agreement): bool
    {
        return false;
    }
}
