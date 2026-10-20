<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages\CreateUnit;
use App\Filament\Resources\UnitResource\Pages\EditUnit;
use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Models\Unit;

class UnitResource extends MasterDataResource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = 'PENGELOLAAN';

    public static function getNavigationLabel(): string
    {
        return __('m1.units');
    }

    public static function getModelLabel(): string
    {
        return __('m1.unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.units');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
