<?php

namespace App\Filament\Resources\LetterTypeResource\Pages;

use App\Filament\Resources\LetterTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLetterType extends EditRecord
{
    protected static string $resource = LetterTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label(__('app.delete'))->requiresConfirmation()];
    }
}
