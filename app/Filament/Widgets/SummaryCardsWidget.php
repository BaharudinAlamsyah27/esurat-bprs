<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SummaryCardsWidget extends Widget
{
    protected static string $view = 'filament.widgets.summary-cards';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    /** @return array<int, array{label: string, value: int}> */
    protected function getCards(): array
    {
        return [
            ['label' => __('app.incoming_this_month'), 'value' => 0],
            ['label' => __('app.outgoing_this_month'), 'value' => 0],
            ['label' => __('app.active_dispositions'), 'value' => 0],
            ['label' => __('app.pending_approvals'), 'value' => 0],
        ];
    }
}
