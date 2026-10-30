<?php

use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\DispositionPrintController;
use App\Http\Controllers\InstitutionLogoController;
use App\Http\Controllers\ReportPrintController;
use App\Http\Controllers\SecureFileController;
use App\Http\Middleware\IdleSessionTimeout;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', IdleSessionTimeout::class])->group(function (): void {
    Route::get('/laporan/{type}/cetak', ReportPrintController::class)->name('reports.print');
    Route::get('/laporan/{type}/pdf', ReportPrintController::class)->name('reports.pdf');
    Route::get('/laporan/{type}/excel', ReportPrintController::class)->name('reports.excel');
    Route::get('/surat-masuk/berkas/{attachment}', [SecureFileController::class, 'show'])
        ->name('incoming-letters.attachments.show');

    Route::get('/surat-keluar/berkas/{attachment}', [SecureFileController::class, 'show'])
        ->name('outgoing-letters.attachments.show');

    Route::get('/disposisi/{disposition}/cetak', DispositionPrintController::class)
        ->name('dispositions.print');

    Route::get('/instansi/logo', [InstitutionLogoController::class, 'show'])
        ->name('institution.logo');

    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('device-tokens.store');

    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy'])
        ->name('device-tokens.destroy');
});
