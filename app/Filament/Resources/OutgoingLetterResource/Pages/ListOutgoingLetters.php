<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\OutgoingLetterResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutgoingLetters extends ListRecords
{
    protected static string $resource = OutgoingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label(__('app.help'))
                ->icon('heroicon-o-question-mark-circle')
                ->modalHeading(__('outgoing.help_title'))
                ->modalDescription(__('outgoing.help_body'))
                ->modalSubmitActionLabel(__('app.close')),
            CreateAction::make()->label(__('outgoing.new')),
        ];
    }
}
