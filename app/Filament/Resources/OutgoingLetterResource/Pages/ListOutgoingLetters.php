<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\Concerns\HasStandardListHeader;
use App\Filament\Resources\OutgoingLetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutgoingLetters extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = OutgoingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('outgoing.new'))->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return __('outgoing.list_intro');
    }
}
