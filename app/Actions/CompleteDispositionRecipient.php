<?php

namespace App\Actions;

use App\Models\DispositionRecipient;
use App\Models\User;
use App\Notifications\DispositionCompleted;
use Illuminate\Auth\Access\AuthorizationException;

class CompleteDispositionRecipient
{
    public function execute(User $user, DispositionRecipient $recipient, ?string $note = null): void
    {
        if ($recipient->user_id !== $user->getKey() && ! $user->isAdmin()) {
            throw new AuthorizationException(__('disposition.unauthorized_recipient'));
        }

        $recipient->markAsDone($note);

        activity('disposition')
            ->causedBy($user)
            ->performedOn($recipient->disposition)
            ->event('completed')
            ->withProperties([
                'disposition_recipient_id' => $recipient->getKey(),
                'user_id' => $user->getKey(),
            ])
            ->log(__('disposition.audit_completed'));

        $creator = $recipient->disposition->creator;
        if ($creator instanceof User && $creator->getKey() !== $user->getKey()) {
            $creator->notify(new DispositionCompleted($recipient));
        }
    }
}
