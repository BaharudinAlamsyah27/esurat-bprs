<?php

namespace App\Filament\Resources\ClassificationResource\Pages;

use App\Filament\Resources\ClassificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassification extends EditRecord
{
    protected static string $resource = ClassificationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label(__('app.delete'))->requiresConfirmation()];
    }
}
