<?php

namespace App\Actions;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ForwardIncomingLetter
{
    public function execute(User $user, IncomingLetter $letter): IncomingLetter
    {
        Gate::forUser($user)->authorize('forward', $letter);

        return DB::transaction(function () use ($letter): IncomingLetter {
            $lockedLetter = IncomingLetter::query()->lockForUpdate()->findOrFail($letter->getKey());

            if ($lockedLetter->initial_recipient_id === null) {
                throw ValidationException::withMessages([
                    'data.initial_recipient_id' => __('incoming.recipient_required'),
                ]);
            }

            $lockedLetter->forceFill(['status' => IncomingLetterStatus::Forwarded])->save();

            return $lockedLetter->refresh();
        });
    }
}
