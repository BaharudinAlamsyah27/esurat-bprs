<?php

namespace App\Filament\Resources\RegisteredDeviceResource\Pages;

use App\Filament\Resources\Concerns\HasStandardListHeader;
use App\Filament\Resources\RegisteredDeviceResource;
use Filament\Resources\Pages\ListRecords;

class ListRegisteredDevices extends ListRecords
{
    use HasStandardListHeader;

    protected static string $resource = RegisteredDeviceResource::class;

    public function getSubheading(): ?string
    {
        return 'Daftar perangkat HP pegawai yang terhubung untuk menerima notifikasi.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
