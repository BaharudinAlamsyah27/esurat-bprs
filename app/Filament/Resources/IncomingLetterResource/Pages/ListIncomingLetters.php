<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Filament\Resources\IncomingLetterResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncomingLetters extends ListRecords
{
    protected static string $resource = IncomingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label(__('app.help'))
                ->icon('heroicon-o-question-mark-circle')
                ->modalHeading(__('incoming.help_title'))
                ->modalDescription(__('incoming.help_body'))
                ->modalSubmitActionLabel(__('app.close')),
            CreateAction::make()->label(__('incoming.new')),
        ];
    }
}
