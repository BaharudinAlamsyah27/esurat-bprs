<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DispositionRecipientStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Read = 'read';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('dispositions.recipient_statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Read => 'info',
            self::InProgress => 'warning',
            self::Done => 'success',
            self::Cancelled => 'danger',
        };
    }
}
