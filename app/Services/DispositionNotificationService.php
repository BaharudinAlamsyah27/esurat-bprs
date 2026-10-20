<?php

namespace App\Services;

use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class DispositionNotificationService
{
    public function notifyCreated(Disposition $disposition): void
    {
        $disposition->loadMissing(['sender', 'parent.sender', 'recipients.recipient', 'recipients.unit']);
        $users = collect();

        foreach ($disposition->recipients as $recipient) {
            $users = $users->merge($this->usersForRecipient($recipient));
        }

        if ($disposition->parent?->sender instanceof User) {
            $users->push($disposition->parent->sender);
        }

        /** @var Collection<int, User> $users */
        $users
            ->filter(fn (User $user): bool => $user->is_active)
            ->unique('id')
            ->each(fn (User $user) => Notification::make()
                ->title(__('dispositions.received'))
                ->body(__('dispositions.received_body', ['sender' => $disposition->sender->name]))
                ->icon('heroicon-o-inbox')
                ->sendToDatabase($user));
    }

    /** @return Collection<int, User> */
    private function usersForRecipient(DispositionRecipient $recipient): Collection
    {
        $users = collect();

        if ($recipient->recipient instanceof User) {
            $users->push($recipient->recipient);
        }

        if ($recipient->unit?->is_active) {
            $users = $users->merge($recipient->unit->users()->where('is_active', true)->get());
        }

        return $users;
    }
}
