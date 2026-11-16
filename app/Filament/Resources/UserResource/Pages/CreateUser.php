<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserLevel;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['level'] ?? null) === UserLevel::SuperAdmin->value && ! (auth()->user()?->isSuperAdmin() ?? false)) {
            throw ValidationException::withMessages(['data.level' => __('m1.superadmin_assignment_forbidden')]);
        }

        return $data;
    }
}
