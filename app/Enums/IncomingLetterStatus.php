<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IncomingLetterStatus: string implements HasColor, HasLabel
{
    case Registered = 'registered';
    case Forwarded = 'forwarded';
    case InDisposition = 'in_disposition';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return __('incoming.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Registered, self::Archived => 'gray',
            self::Forwarded => 'info',
            self::InDisposition => 'warning',
            self::InProgress => 'primary',
            self::Completed => 'success',
        };
    }
}
