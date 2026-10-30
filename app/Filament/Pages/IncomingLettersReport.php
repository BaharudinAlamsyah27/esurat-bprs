<?php

namespace App\Filament\Pages;

use App\Filament\Support\LetterReportPage;

class IncomingLettersReport extends LetterReportPage
{
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function getNavigationLabel(): string
    {
        return __('reports.incoming_navigation');
    }

    public static function getSlug(): string
    {
        return 'laporan/surat-masuk';
    }

    protected function reportType(): string
    {
        return 'incoming';
    }

    protected function reportTitle(): string
    {
        return __('reports.incoming_title');
    }

    /** @return array<string, string> */
    protected function dateOptions(): array
    {
        return [
            'letter_date' => __('reports.letter_date'),
            'received_at' => __('reports.received_date'),
        ];
    }
}
