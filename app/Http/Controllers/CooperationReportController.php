<?php

namespace App\Http\Controllers;

use App\Exports\CooperationAgreementsExport;
use App\Models\CooperationAgreement;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class CooperationReportController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $validated = validator($request->query(), [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ])->validate();

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages(['end_date' => __('reports.date_range_too_long')]);
        }

        $filters = [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];

        $title = __('reports.cooperation_title');

        if ($request->routeIs('reports.cooperation.excel')) {
            return Excel::download(
                new CooperationAgreementsExport($filters),
                'laporan-perjanjian-kerjasama.xlsx'
            );
        }

        $agreements = CooperationAgreement::query()
            ->whereDate('agreement_date', '>=', $filters['start_date'])
            ->whereDate('agreement_date', '<=', $filters['end_date'])
            ->orderBy('agreement_date')
            ->orderBy('id')
            ->get();

        $institution = Institution::query()->first();
        $viewData = compact('agreements', 'institution', 'title', 'filters');

        if ($request->routeIs('reports.cooperation.pdf')) {
            return Pdf::loadView('reports.cooperation-print', $viewData)
                ->setPaper('a4', 'landscape')
                ->download('laporan-perjanjian-kerjasama.pdf');
        }

        return view('reports.cooperation-print', $viewData);
    }
}
