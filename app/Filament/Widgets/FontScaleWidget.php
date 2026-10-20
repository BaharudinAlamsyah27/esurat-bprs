<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FontScaleWidget extends Widget
{
    // ASSUMPTION: simpan pilihan ukuran huruf di browser sampai profil pengguna ditambahkan pada M1.
    protected static string $view = 'filament.widgets.font-scale';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';
}
