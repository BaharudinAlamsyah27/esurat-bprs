<?php

namespace App\Actions;

use App\Enums\DispositionRecipientStatus;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class MarkDispositionRead
{
    public function execute(User $user, DispositionRecipient $recipient): void
    {
        if ($recipient->user_id !== $user->getKey() && ! $user->isAdmin()) {
            throw new AuthorizationException(__('disposition.unauthorized_recipient'));
        }

        if ($recipient->status === DispositionRecipientStatus::New) {
            $recipient->markAsRead();

            activity('disposition')
                ->causedBy($user)
                ->performedOn($recipient->disposition)
                ->event('read')
                ->withProperties([
                    'disposition_recipient_id' => $recipient->getKey(),
                    'user_id' => $user->getKey(),
                ])
                ->log(__('disposition.audit_read'));
        }
    }
}
