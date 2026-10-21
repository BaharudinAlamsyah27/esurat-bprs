<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Services\DispositionPdfService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DispositionPrintController
{
    public function __invoke(Disposition $disposition, DispositionPdfService $pdf): Response
    {
        Gate::authorize('print', $disposition);

        return $pdf->stream($disposition);
    }
}
