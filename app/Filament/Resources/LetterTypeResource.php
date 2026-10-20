<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterTypeResource\Pages\CreateLetterType;
use App\Filament\Resources\LetterTypeResource\Pages\EditLetterType;
use App\Filament\Resources\LetterTypeResource\Pages\ListLetterTypes;
use App\Models\LetterType;

class LetterTypeResource extends MasterDataResource
{
    protected static ?string $model = LetterType::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationGroup = 'LANJUTAN';

    public static function getNavigationLabel(): string
    {
        return __('m1.letter_types');
    }

    public static function getModelLabel(): string
    {
        return __('m1.letter_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.letter_types');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLetterTypes::route('/'),
            'create' => CreateLetterType::route('/create'),
            'edit' => EditLetterType::route('/{record}/edit'),
        ];
    }
}
