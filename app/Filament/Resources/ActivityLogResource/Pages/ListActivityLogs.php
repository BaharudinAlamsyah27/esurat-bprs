<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\Concerns\HasStandardListHeader;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = ActivityLogResource::class;

    public function getSubheading(): ?string
    {
        return __('m1.activity_list_intro');
    }
}
