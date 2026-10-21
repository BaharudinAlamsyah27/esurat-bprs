<?php

namespace App\Exports;

use App\Models\OutgoingLetter;
use App\Services\LetterReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** @implements WithMapping<OutgoingLetter> */
class OutgoingLettersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    /** @param array<string, mixed> $filters */
    public function __construct(private readonly array $filters) {}

    /** @return Builder<OutgoingLetter> */
    public function query(): Builder
    {
        return app(LetterReportService::class)->outgoingQuery($this->filters);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            __('reports.number'), __('reports.letter_number'), __('reports.agenda_number'), __('reports.subject'),
            __('reports.recipient'), __('reports.classification'), __('reports.letter_date'), __('reports.sent_date'), __('reports.note'),
        ];
    }

    /** @param OutgoingLetter $row
     * @return array<int, int|string|null>
     */
    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row->letter_number,
            $row->agenda_number,
            $row->subject,
            $row->recipient,
            $row->classification?->code,
            Carbon::parse($row->letter_date)->format('d-m-Y'),
            filled($row->sent_date) ? Carbon::parse($row->sent_date)->format('d-m-Y') : null,
            $row->note,
        ];
    }
}
