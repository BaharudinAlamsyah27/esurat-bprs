<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GreetingWidget extends Widget
{
    protected static string $view = 'filament.widgets.greeting';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';
}
