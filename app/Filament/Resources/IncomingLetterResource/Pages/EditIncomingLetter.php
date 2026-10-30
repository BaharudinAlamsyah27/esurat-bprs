<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Filament\Resources\IncomingLetterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncomingLetter extends EditRecord
{
    protected static string $resource = IncomingLetterResource::class;

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
                ->modalHeading(__('incoming.delete_confirmation_title'))
                ->modalDescription(__('incoming.delete_confirmation_body'))
                ->modalSubmitActionLabel(__('app.confirm_delete'))
                ->modalCancelActionLabel(__('app.cancel')),
        ];
    }
}
