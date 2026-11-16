<?php

namespace App\Filament\Resources\CooperationAgreementResource\Pages;

use App\Filament\Resources\Concerns\HasStandardListHeader;
use App\Filament\Resources\CooperationAgreementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCooperationAgreements extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = CooperationAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('cooperation.new'))->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return __('cooperation.list_intro');
    }
}
