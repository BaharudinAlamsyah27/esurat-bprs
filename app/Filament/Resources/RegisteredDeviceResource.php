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
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('m1.registered_devices');
    }

    public static function getModelLabel(): string
    {
        return 'Perangkat';
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
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device_name')
                    ->label('Nama Perangkat')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge(),
                TextColumn::make('app_version')
                    ->label('Versi Aplikasi')
                    ->placeholder('—'),
                TextColumn::make('last_used_at')
                    ->label('Terakhir Aktif')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Cabut')
                    ->modalHeading('Cabut Akses Perangkat?')
                    ->modalDescription('Perangkat ini tidak akan menerima notifikasi sampai masuk kembali.')
                    ->modalSubmitActionLabel('Ya, Cabut'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegisteredDevices::route('/'),
        ];
    }
}
