<?php

namespace App\Actions;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArchiveIncomingLetter
{
    public function execute(User $user, IncomingLetter $letter, ?string $location = null): IncomingLetter
    {
        Gate::forUser($user)->authorize('archive', $letter);

        return DB::transaction(function () use ($letter, $location): IncomingLetter {
            $lockedLetter = IncomingLetter::query()->lockForUpdate()->findOrFail($letter->getKey());
            $lockedLetter->forceFill([
                'status' => IncomingLetterStatus::Archived,
                'archive_location' => $location,
                'archived_at' => now(),
            ])->save();

            return $lockedLetter->refresh();
        });
    }
}
