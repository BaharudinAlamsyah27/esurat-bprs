<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\OutgoingLetterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOutgoingLetter extends CreateRecord
{
    protected static string $resource = OutgoingLetterResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return OutgoingLetterResource::register($data);
    }
}
