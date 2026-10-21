<?php

namespace App\Services;

use App\Models\Disposition;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DispositionPdfService
{
    public function stream(Disposition $disposition): Response
    {
        $disposition->loadMissing(['incomingLetter.classification', 'incomingLetter.registrar']);
        $institution = Institution::query()->first();
        $logoData = null;

        if ($institution?->logo_path && Storage::disk('private')->exists($institution->logo_path)) {
            $contents = Storage::disk('private')->get($institution->logo_path);
            $mime = Storage::disk('private')->mimeType($institution->logo_path) ?: 'image/png';
            $logoData = 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        $pdf = Pdf::loadView('pdf.disposition', [
            'disposition' => $disposition,
            'letter' => $disposition->incomingLetter,
            'institution' => $institution,
            'logoData' => $logoData,
            'instructionLabels' => $disposition->instructionLabels(),
            'dispositionDate' => $this->indonesianDate($disposition->disposition_date),
        ])->setPaper('a4');

        $contents = $pdf->output();

        DB::transaction(function () use ($disposition): void {
            $locked = Disposition::query()->whereKey($disposition->getKey())->lockForUpdate()->firstOrFail();
            $locked->increment('print_count');
            $locked->forceFill(['printed_at' => now()])->save();

            activity('disposition')
                ->causedBy(auth()->user())
                ->performedOn($locked)
                ->event('printed')
                ->withProperties([
                    'incoming_letter_id' => $locked->incoming_letter_id,
                    'print_count' => $locked->print_count,
                ])
                ->log(__('disposition.audit_printed'));
        });

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lembar-disposisi-'.str_replace('/', '-', $disposition->incomingLetter->agenda_number).'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function indonesianDate(\DateTimeInterface $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return (int) $date->format('j').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
