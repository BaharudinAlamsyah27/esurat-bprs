<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Actions\RegisterIncomingLetter as RegisterIncomingLetterAction;
use App\Filament\Resources\IncomingLetterResource;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateIncomingLetter extends CreateRecord
{
    protected static string $resource = IncomingLetterResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $files = $data['scans'] ?? [];
        $confirmedDuplicate = (bool) ($data['confirm_duplicate'] ?? false);
        unset($data['scans'], $data['confirm_duplicate']);

        $letterNumber = trim((string) ($data['letter_number'] ?? ''));
        $isDuplicate = $letterNumber !== '' && IncomingLetter::query()
            ->where('letter_number', $letterNumber)
            ->where('sender_name', $data['sender_name'] ?? null)
            ->whereDate('letter_date', $data['letter_date'] ?? null)
            ->exists();

        if ($isDuplicate && ! $confirmedDuplicate) {
            Notification::make()
                ->title(__('incoming.duplicate_warning'))
                ->warning()
                ->send();

            throw ValidationException::withMessages([
                'data.confirm_duplicate' => __('incoming.duplicate_confirmation_required'),
            ]);
        }

        /** @var User $user */
        $user = auth()->user();

        return app(RegisterIncomingLetterAction::class)->execute($user, $data, is_array($files) ? $files : []);
    }
}
