<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LetterConfidentiality: string implements HasColor, HasLabel
{
    case Ordinary = 'biasa';
    case Restricted = 'terbatas';
    case Secret = 'rahasia';

    public function getLabel(): string
    {
        return __('incoming.confidentialities.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ordinary => 'success',
            self::Restricted => 'warning',
            self::Secret => 'danger',
        };
    }
}
