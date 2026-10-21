<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ClassificationResource;
use App\Filament\Resources\IncomingLetterResource;
use App\Filament\Resources\OutgoingLetterResource;
use App\Filament\Resources\UserResource;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class SummaryCardsWidget extends Widget
{
    protected static string $view = 'filament.widgets.summary-cards';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    /** @return array<int, array{label: string, value: int, url: string}> */
    public function getCards(): array
    {
        $counts = Cache::remember('dashboard.summary-counts.v1', now()->addSeconds(60), fn (): array => [
            'incoming' => IncomingLetter::query()->count(),
            'outgoing' => OutgoingLetter::query()->count(),
            'disposed' => IncomingLetter::query()->has('disposition')->count(),
            'not_disposed' => IncomingLetter::query()->doesntHave('disposition')->count(),
            'classifications' => Classification::query()->count(),
            'users' => User::query()->count(),
        ]);

        $cards = [
            ['label' => __('app.incoming_total'), 'value' => $counts['incoming'], 'url' => IncomingLetterResource::getUrl('index')],
            ['label' => __('app.outgoing_total'), 'value' => $counts['outgoing'], 'url' => OutgoingLetterResource::getUrl('index')],
            [
                'label' => __('app.dispositions_done'),
                'value' => $counts['disposed'],
                'url' => IncomingLetterResource::getUrl('index', [
                    'tableFilters' => ['has_disposition' => ['value' => 'yes']],
                ]),
            ],
            [
                'label' => __('app.dispositions_pending'),
                'value' => $counts['not_disposed'],
                'url' => IncomingLetterResource::getUrl('index', [
                    'tableFilters' => ['has_disposition' => ['value' => 'no']],
                ]),
            ],
        ];

        if (auth()->user()?->isAdmin()) {
            $cards[] = ['label' => __('app.classifications_total'), 'value' => $counts['classifications'], 'url' => ClassificationResource::getUrl('index')];
            $cards[] = ['label' => __('app.users_total'), 'value' => $counts['users'], 'url' => UserResource::getUrl('index')];
        }

        return $cards;
    }
}
