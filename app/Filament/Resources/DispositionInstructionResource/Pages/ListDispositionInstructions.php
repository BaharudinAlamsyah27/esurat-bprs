<?php

namespace App\Filament\Resources\DispositionInstructionResource\Pages;

use App\Filament\Resources\DispositionInstructionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDispositionInstructions extends ListRecords
{
    protected static string $resource = DispositionInstructionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('app.create'))];
    }
}
