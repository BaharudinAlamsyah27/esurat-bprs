<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IncomingDeliveryMethod: string implements HasLabel
{
    case Direct = 'langsung';
    case Post = 'pos';
    case Courier = 'kurir';
    case Email = 'email';

    public function getLabel(): string
    {
        return __('incoming.delivery_methods.'.$this->value);
    }
}
