<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\IncomingLetterResource;
use App\Filament\Resources\OutgoingLetterResource;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class LatestLettersWidget extends Widget
{
    protected static string $view = 'filament.widgets.latest-letters';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /** @return array{incoming: array<int, array{number: string, subject: string, sender: string, date: string, url: string}>, outgoing: array<int, array{number: string, subject: string, recipient: string, date: string, url: string}>} */
    protected function getLetters(): array
    {
        return Cache::remember('dashboard.latest-letters.v1', now()->addSeconds(60), function (): array {
            $incoming = IncomingLetter::query()->latest('letter_date')->limit(5)->get();
            $outgoing = OutgoingLetter::query()->latest('letter_date')->limit(5)->get();

            return [
                'incoming' => $incoming->map(fn (IncomingLetter $letter): array => [
                    'number' => $letter->letter_number,
                    'subject' => $letter->subject,
                    'sender' => $letter->sender_name,
                    'date' => Carbon::parse($letter->letter_date)->format('d-m-Y'),
                    'url' => IncomingLetterResource::getUrl('view', ['record' => $letter]),
                ])->all(),
                'outgoing' => $outgoing->map(fn (OutgoingLetter $letter): array => [
                    'number' => $letter->letter_number,
                    'subject' => $letter->subject,
                    'recipient' => $letter->recipient,
                    'date' => Carbon::parse($letter->letter_date)->format('d-m-Y'),
                    'url' => OutgoingLetterResource::getUrl('view', ['record' => $letter]),
                ])->all(),
            ];
        });
    }
}
