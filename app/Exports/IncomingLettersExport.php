<?php

namespace App\Exports;

use App\Models\IncomingLetter;
use App\Services\LetterReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** @implements WithMapping<IncomingLetter> */
class IncomingLettersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    /** @param array<string, mixed> $filters */
    public function __construct(private readonly array $filters) {}

    /** @return Builder<IncomingLetter> */
    public function query(): Builder
    {
        return app(LetterReportService::class)->incomingQuery($this->filters);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            __('reports.number'), __('reports.letter_number'), __('reports.agenda_number'), __('reports.subject'),
            __('reports.sender'), __('reports.classification'), __('reports.letter_date'), __('reports.received_date'), __('reports.note'),
        ];
    }

    /** @param IncomingLetter $row
     * @return array<int, int|string|null>
     */
    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row->letter_number,
            $row->agenda_number,
            $row->subject,
            $row->sender_name,
            $row->classification?->code,
            Carbon::parse($row->letter_date)->format('d-m-Y'),
            Carbon::parse($row->received_at)->format('d-m-Y'),
            $row->note,
        ];
    }
}
