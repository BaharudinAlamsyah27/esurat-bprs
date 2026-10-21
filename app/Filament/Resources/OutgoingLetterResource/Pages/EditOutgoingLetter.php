<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\OutgoingLetterResource;
use App\Models\OutgoingLetter;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class EditOutgoingLetter extends EditRecord
{
    protected static string $resource = OutgoingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof OutgoingLetter) {
            throw new LogicException('Unexpected record type for outgoing letter.');
        }

        return OutgoingLetterResource::update($record, $data);
    }
}
