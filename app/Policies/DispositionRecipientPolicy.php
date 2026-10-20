<?php

namespace App\Policies;

use App\Models\DispositionRecipient;
use App\Models\User;

class DispositionRecipientPolicy
{
    public function update(User $user, DispositionRecipient $recipient): bool
    {
        return $user->can('disposition.update_own')
            && $recipient->disposition->incomingLetter->status->value !== 'archived'
            && ((int) $recipient->recipient_user_id === (int) $user->id
                || ($recipient->recipient_unit_id !== null && (int) $recipient->recipient_unit_id === (int) $user->unit_id));
    }
}
