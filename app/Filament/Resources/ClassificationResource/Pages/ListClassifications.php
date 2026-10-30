<?php

namespace App\Filament\Resources\ClassificationResource\Pages;

use App\Filament\Resources\ClassificationResource;
use App\Filament\Resources\Concerns\HasStandardListHeader;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassifications extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = ClassificationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('m1.add_classification'))->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return __('m1.classification_list_intro');
    }
}
