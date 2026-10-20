<?php

namespace App\Filament\Resources\DispositionInstructionResource\Pages;

use App\Filament\Resources\DispositionInstructionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDispositionInstruction extends EditRecord
{
    protected static string $resource = DispositionInstructionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label(__('app.delete'))->requiresConfirmation()];
    }
}
