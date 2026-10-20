<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages\CreatePosition;
use App\Filament\Resources\PositionResource\Pages\EditPosition;
use App\Filament\Resources\PositionResource\Pages\ListPositions;
use App\Models\Position;

class PositionResource extends MasterDataResource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'LANJUTAN';

    public static function getNavigationLabel(): string
    {
        return __('m1.positions');
    }

    public static function getModelLabel(): string
    {
        return __('m1.position');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.positions');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPositions::route('/'),
            'create' => CreatePosition::route('/create'),
            'edit' => EditPosition::route('/{record}/edit'),
        ];
    }
}
