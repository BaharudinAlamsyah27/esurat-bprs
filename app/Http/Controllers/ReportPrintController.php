<?php

namespace App\Http\Controllers;

use App\Exports\IncomingLettersExport;
use App\Exports\OutgoingLettersExport;
use App\Models\Institution;
use App\Services\LetterReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportPrintController extends Controller
{
    public function __invoke(Request $request, string $type, LetterReportService $reports): mixed
    {
        abort_unless(in_array($type, ['incoming', 'outgoing'], true), 404);

        $filters = $reports->validatedFilters($type, $request->query());
        $title = $type === 'incoming' ? __('reports.incoming_title') : __('reports.outgoing_title');

        if ($request->routeIs('reports.excel')) {
            $export = $type === 'incoming'
                ? new IncomingLettersExport($filters)
                : new OutgoingLettersExport($filters);

            return Excel::download($export, str_replace(' ', '-', strtolower($title)).'.xlsx');
        }

        $letters = $reports->query($type, $filters)->get();
        $institution = Institution::query()->first();
        $viewData = compact('letters', 'institution', 'title', 'type', 'filters');

        if ($request->routeIs('reports.pdf')) {
            return Pdf::loadView('reports.print', $viewData)
                ->setPaper('a4', 'landscape')
                ->download(str_replace(' ', '-', strtolower($title)).'.pdf');
        }

        return view('reports.print', $viewData);
    }
}
