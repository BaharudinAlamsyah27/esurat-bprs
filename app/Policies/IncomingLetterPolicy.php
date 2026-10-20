<?php

namespace App\Policies;

use App\Enums\IncomingLetterStatus;
use App\Models\Concerns\VisibleToUserScope;
use App\Models\IncomingLetter;
use App\Models\User;

class IncomingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incoming_letter.view_any') || $user->can('incoming_letter.view');
    }

    public function view(User $user, IncomingLetter $incomingLetter): bool
    {
        return $this->canViewRecords($user) && VisibleToUserScope::canSee($user, $incomingLetter);
    }

    public function viewContents(User $user, IncomingLetter $incomingLetter): bool
    {
        return $this->view($user, $incomingLetter) && ! $incomingLetter->isMetadataOnlyFor($user);
    }

    public function viewAttachments(User $user, IncomingLetter $incomingLetter): bool
    {
        return $this->viewContents($user, $incomingLetter);
    }

    public function create(User $user): bool
    {
        return $user->can('incoming_letter.create');
    }

    public function update(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->can('incoming_letter.update')
            && $incomingLetter->status !== IncomingLetterStatus::Archived
            && $this->viewContents($user, $incomingLetter);
    }

    public function forward(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->can('incoming_letter.forward')
            && $incomingLetter->status === IncomingLetterStatus::Registered;
    }

    public function archive(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->can('incoming_letter.archive')
            && $incomingLetter->status === IncomingLetterStatus::Completed;
    }

    public function close(User $user, IncomingLetter $incomingLetter): bool
    {
        return $user->can('disposition.close')
            && ! in_array($incomingLetter->status, [IncomingLetterStatus::Completed, IncomingLetterStatus::Archived], true)
            && $incomingLetter->dispositions()->where('from_user_id', $user->id)->exists()
            && $this->viewContents($user, $incomingLetter);
    }

    public function delete(User $user, IncomingLetter $incomingLetter): bool
    {
        return false;
    }

    public function restore(User $user, IncomingLetter $incomingLetter): bool
    {
        return false;
    }

    public function forceDelete(User $user, IncomingLetter $incomingLetter): bool
    {
        return false;
    }

    private function canViewRecords(User $user): bool
    {
        return $user->can('incoming_letter.view_any') || $user->can('incoming_letter.view');
    }
}
