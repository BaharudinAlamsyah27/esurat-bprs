<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserLevel;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        $currentLevel = (string) $record->getRawOriginal('level');
        $newLevel = (string) ($data['level'] ?? $currentLevel);
        if ($record->is(auth()->user()) && $newLevel !== $currentLevel) {
            throw ValidationException::withMessages([
                'data.level' => __('m1.cannot_change_own_level'),
            ]);
        }

        $removesLastActiveAdmin = $record->isAdmin()
            && $record->is_active
            && ((string) $newLevel !== UserLevel::Admin->value || ! (bool) ($data['is_active'] ?? $record->is_active))
            && User::query()
                ->where('level', UserLevel::Admin->value)
                ->where('is_active', true)
                ->count() <= 1;

        if ($removesLastActiveAdmin) {
            throw ValidationException::withMessages([
                'data.level' => __('m1.last_active_admin'),
            ]);
        }

        return $data;
    }
}
