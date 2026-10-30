<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Concerns\HasStandardListHeader;
use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('m1.add_user'))->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return __('m1.user_list_intro');
    }
}
