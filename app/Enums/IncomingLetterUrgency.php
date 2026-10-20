<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IncomingLetterUrgency: string implements HasColor, HasLabel
{
    case Normal = 'biasa';
    case Important = 'penting';
    case Urgent = 'segera';
    case VeryUrgent = 'sangat_segera';

    public function getLabel(): string
    {
        return __('incoming.urgencies.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Normal => 'gray',
            self::Important => 'info',
            self::Urgent => 'warning',
            self::VeryUrgent => 'danger',
        };
    }
}
