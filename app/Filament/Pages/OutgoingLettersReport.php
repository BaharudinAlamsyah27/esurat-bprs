<?php

namespace App\Filament\Pages;

use App\Filament\Support\LetterReportPage;

class OutgoingLettersReport extends LetterReportPage
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    public static function getNavigationLabel(): string
    {
        return __('reports.outgoing_navigation');
    }

    public static function getSlug(): string
    {
        return 'laporan/surat-keluar';
    }

    protected function reportType(): string
    {
        return 'outgoing';
    }

    protected function reportTitle(): string
    {
        return __('reports.outgoing_title');
    }

    /** @return array<string, string> */
    protected function dateOptions(): array
    {
        return [
            'letter_date' => __('reports.letter_date'),
            'sent_date' => __('reports.sent_date'),
        ];
    }
}
