<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassificationResource\Pages\CreateClassification;
use App\Filament\Resources\ClassificationResource\Pages\EditClassification;
use App\Filament\Resources\ClassificationResource\Pages\ListClassifications;
use App\Models\Classification;

class ClassificationResource extends MasterDataResource
{
    protected static ?string $model = Classification::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'DATA MASTER';

    public static function getNavigationLabel(): string
    {
        return __('m1.classifications');
    }

    public static function getModelLabel(): string
    {
        return __('m1.classification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.classifications');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassifications::route('/'),
            'create' => CreateClassification::route('/create'),
            'edit' => EditClassification::route('/{record}/edit'),
        ];
    }
}
