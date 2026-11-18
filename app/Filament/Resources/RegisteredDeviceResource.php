<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegisteredDeviceResource\Pages\ListRegisteredDevices;
use App\Models\DeviceToken;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegisteredDeviceResource extends Resource
{
    protected static ?string $model = DeviceToken::class;

    protected static ?string $slug = 'perangkat-terdaftar';

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'DATA MASTER';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('m1.registered_devices');
    }

    public static function getModelLabel(): string
    {
        return __('m1.device_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.registered_devices');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('m1.user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device_name')
                    ->label(__('m1.device_name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('platform')
                    ->label(__('m1.device_platform'))
                    ->badge(),
                TextColumn::make('app_version')
                    ->label(__('m1.device_app_version'))
                    ->placeholder('—'),
                TextColumn::make('last_used_at')
                    ->label(__('m1.device_last_active'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->actions([
                DeleteAction::make()
                    ->label(__('m1.device_revoke'))
                    ->modalHeading(__('m1.device_revoke_heading'))
                    ->modalDescription(__('m1.device_revoke_description'))
                    ->modalSubmitActionLabel(__('m1.device_revoke_confirm')),
            ])
            ->defaultSort('last_used_at', 'desc')
            ->striped()
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(__('app.no_data'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegisteredDevices::route('/'),
        ];
    }
}
