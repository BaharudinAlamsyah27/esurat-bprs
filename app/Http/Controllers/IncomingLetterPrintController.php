<?php

namespace App\Http\Controllers;

use App\Models\IncomingLetter;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class IncomingLetterPrintController
{
    public function show(IncomingLetter $incomingLetter): Response
    {
        Gate::authorize('viewContents', $incomingLetter);

        $filename = 'Lembar-Disposisi-'.str_replace('/', '-', $incomingLetter->agenda_number).'.pdf';

        return Pdf::loadView('pdf.incoming-disposition', [
            'letter' => $incomingLetter->load(['letterType', 'classification', 'registrar']),
            'institutionName' => Setting::getValue('institution_name', 'BPRS Sragen'),
            'institutionAddress' => Setting::getValue('institution_address', ''),
        ])
            ->setPaper('a4')
            ->stream($filename);
    }
}
