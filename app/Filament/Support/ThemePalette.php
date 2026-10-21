<?php

namespace App\Filament\Support;

use App\Models\Setting;
use Filament\Support\Colors\Color;

class ThemePalette
{
    /** @var array<string, array{primary: string, surface: string, active: string, dark_surface: string, dark_active: string}> */
    private const PALETTES = [
        'green' => ['primary' => '#0F5C46', 'surface' => '#EAF3EF', 'active' => '#DCEDE5', 'dark_surface' => '#213D35', 'dark_active' => '#315749'],
        'blue' => ['primary' => '#2457A6', 'surface' => '#EDF3FC', 'active' => '#DCE8F8', 'dark_surface' => '#263B58', 'dark_active' => '#345579'],
        'maroon' => ['primary' => '#963B4B', 'surface' => '#F8EEF0', 'active' => '#F0DDE1', 'dark_surface' => '#49313A', 'dark_active' => '#68404B'],
    ];

    public static function selected(): string
    {
        $value = Setting::query()->where('key', 'ui_theme_color')->first()?->value;
        $color = is_array($value) ? ($value['color'] ?? null) : null;

        return is_string($color) && isset(self::PALETTES[$color]) ? $color : 'green';
    }

    /** @return array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}> */
    public static function filamentColors(): array
    {
        return ['primary' => Color::hex(self::PALETTES[self::selected()]['primary'])];
    }

    public static function styles(): string
    {
        $palette = self::PALETTES[self::selected()];

        return '<style>:root{--esurat-green:'.$palette['primary'].';--esurat-green-hover:'.$palette['primary'].';--esurat-brand-surface:'.$palette['surface'].';--esurat-active:'.$palette['active'].';--esurat-dark-brand-surface:'.$palette['dark_surface'].';--esurat-dark-active:'.$palette['dark_active'].';}</style>';
    }
}
