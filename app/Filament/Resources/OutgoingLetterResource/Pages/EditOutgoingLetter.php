<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\OutgoingLetterResource;
use App\Models\OutgoingLetter;
use Filament\Actions\DeleteAction;
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

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make()
                ->label(__('app.delete'))
                ->modalHeading(__('outgoing.delete_confirmation_title'))
                ->modalDescription(__('outgoing.delete_confirmation_body'))
                ->modalSubmitActionLabel(__('app.confirm_delete'))
                ->modalCancelActionLabel(__('app.cancel')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof OutgoingLetter) {
            throw new LogicException('Unexpected record type for outgoing letter.');
        }

        return OutgoingLetterResource::update($record, $data);
    }
}
