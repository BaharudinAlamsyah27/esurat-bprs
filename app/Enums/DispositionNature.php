<?php

namespace App\Enums;

enum DispositionNature: string
{
    case Biasa = 'biasa';
    case Penting = 'penting';
    case Segera = 'segera';
    case SangatSegera = 'sangat_segera';

    public function label(): string
    {
        return __('disposition.natures.'.$this->value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
