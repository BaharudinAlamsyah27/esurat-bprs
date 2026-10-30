<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Filament\Resources\Concerns\HasStandardListHeader;
use App\Filament\Resources\IncomingLetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncomingLetters extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = IncomingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('incoming.new'))->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return __('incoming.list_intro');
    }
}
