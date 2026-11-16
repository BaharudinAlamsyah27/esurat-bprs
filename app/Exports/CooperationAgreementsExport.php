<?php

namespace App\Exports;

use App\Models\CooperationAgreement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** @implements WithMapping<CooperationAgreement> */
class CooperationAgreementsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    /** @param array{start_date: string, end_date: string} $filters */
    public function __construct(private readonly array $filters) {}

    /** @return Builder<CooperationAgreement> */
    public function query(): Builder
    {
        return CooperationAgreement::query()
            ->whereDate('agreement_date', '>=', $this->filters['start_date'])
            ->whereDate('agreement_date', '<=', $this->filters['end_date'])
            ->orderBy('agreement_date')
            ->orderBy('id');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            __('reports.number'),
            __('cooperation.bss_pks_number'),
            __('cooperation.partner_pks_number'),
            __('cooperation.partner_name'),
            __('cooperation.partner_npwp'),
            __('cooperation.agreement_date'),
            __('cooperation.subject'),
            __('cooperation.pic_name'),
            __('cooperation.note'),
        ];
    }

    /** @param CooperationAgreement $row
     * @return array<int, int|string|null>
     */
    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row->bss_pks_number,
            $row->partner_pks_number,
            $row->partner_name,
            $row->partner_npwp,
            Carbon::parse($row->agreement_date)->format('d-m-Y'),
            $row->subject,
            $row->pic_name,
            $row->note,
        ];
    }
}
