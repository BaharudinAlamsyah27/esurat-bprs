<?php

namespace App\Filament\Resources\Concerns;

trait HasStandardListHeader
{
    /** @return array<string, string> */
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
