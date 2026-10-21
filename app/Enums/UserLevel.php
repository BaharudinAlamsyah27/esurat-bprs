<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserLevel: string implements HasLabel
{
    case Admin = 'admin';
    case User = 'user';

    public function getLabel(): string
    {
        return __('app.user_levels.'.$this->value);
    }
}
