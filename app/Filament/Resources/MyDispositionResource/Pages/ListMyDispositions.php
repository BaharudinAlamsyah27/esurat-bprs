<?php

namespace App\Filament\Resources\MyDispositionResource\Pages;

use App\Filament\Resources\Concerns\HasStandardListHeader;
use App\Filament\Resources\MyDispositionResource;
use Filament\Resources\Pages\ListRecords;

class ListMyDispositions extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = MyDispositionResource::class;

    public function getSubheading(): ?string
    {
        return __('disposition.my_dispositions_subtitle');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
