<?php

use App\Http\Controllers\InstitutionLogoController;
use App\Http\Controllers\SecureFileController;
use App\Http\Middleware\IdleSessionTimeout;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', IdleSessionTimeout::class])->group(function (): void {
    Route::get('/surat-masuk/berkas/{attachment}', [SecureFileController::class, 'show'])
        ->name('incoming-letters.attachments.show');

    Route::get('/instansi/logo', [InstitutionLogoController::class, 'show'])
        ->name('institution.logo');
});
