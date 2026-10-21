<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IncomingLetterStatus: string implements HasColor, HasLabel
{
    case Registered = 'registered';

    public function getLabel(): string
    {
        return __('incoming.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
