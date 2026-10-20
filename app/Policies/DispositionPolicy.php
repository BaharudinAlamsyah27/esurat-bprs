<?php

namespace App\Policies;

use App\Enums\IncomingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;

class DispositionPolicy
{
    public function create(User $user, IncomingLetter $letter, ?Disposition $parent = null): bool
    {
        if (! $user->can('disposition.create') || ! $user->can('viewContents', $letter)
            || ! in_array($letter->status, [IncomingLetterStatus::Forwarded, IncomingLetterStatus::InDisposition, IncomingLetterStatus::InProgress], true)) {
            return false;
        }

        if ($parent === null) {
            return (int) $letter->initial_recipient_id === (int) $user->id;
        }

        return (int) $parent->incoming_letter_id === (int) $letter->id
            && $parent->recipients()->whereIn('status', ['pending', 'read', 'in_progress'])->where(function ($query) use ($user): void {
                $query->where('recipient_user_id', $user->id);
                if ($user->unit_id !== null) {
                    $query->orWhere('recipient_unit_id', $user->unit_id);
                }
            })->exists();
    }

    public function view(User $user, Disposition $disposition): bool
    {
        if (! $user->can('view', $disposition->incomingLetter)) {
            return false;
        }

        return $user->hasRole('direksi')
            || $disposition->from_user_id === $user->id
            || $disposition->recipients()->where(function ($query) use ($user): void {
                $query->where('recipient_user_id', $user->id);
                if ($user->unit_id !== null) {
                    $query->orWhere('recipient_unit_id', $user->unit_id);
                }
            })->exists();
    }
}
