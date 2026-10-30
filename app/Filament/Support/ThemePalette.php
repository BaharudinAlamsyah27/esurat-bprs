<?php

namespace App\Filament\Support;

use App\Models\Setting;
use Filament\Support\Colors\Color;

class ThemePalette
{
    /** @var array<string, array{primary: string, hover: string, active: string}> */
    private const PALETTES = [
        'green' => ['primary' => '#0F5C46', 'hover' => '#0B4938', 'active' => '#EAF3EF'],
        'blue' => ['primary' => '#2457A6', 'hover' => '#1D4687', 'active' => '#EAF0FA'],
        'maroon' => ['primary' => '#762D3A', 'hover' => '#5D202C', 'active' => '#F6EAEC'],
        'teal' => ['primary' => '#0F766E', 'hover' => '#115E59', 'active' => '#E6F3F1'],
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'green' => __('m1.theme_green'),
            'blue' => __('m1.theme_blue'),
            'maroon' => __('m1.theme_maroon'),
            'teal' => __('m1.theme_teal'),
        ];
    }

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

        return '<style>:root{--esurat-accent:'.$palette['primary'].';--esurat-accent-hover:'.$palette['hover'].';--esurat-active:'.$palette['active'].';--esurat-brand-surface:#ffffff;--esurat-dark-brand-surface:#243443}.fi-sidebar,.fi-sidebar-header{background-color:var(--esurat-brand-surface)!important}.dark .fi-sidebar,.dark .fi-sidebar-header{background-color:var(--esurat-dark-brand-surface)!important}</style>';
    }
}
