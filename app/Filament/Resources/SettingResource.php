<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages\CreateSetting;
use App\Filament\Resources\SettingResource\Pages\EditSetting;
use App\Filament\Resources\SettingResource\Pages\ListSettings;
use App\Models\Setting;

class SettingResource extends MasterDataResource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'LANJUTAN';

    public static function getNavigationLabel(): string
    {
        return __('m1.settings');
    }

    public static function getModelLabel(): string
    {
        return __('m1.setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.settings');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
