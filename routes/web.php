<?php

use App\Http\Controllers\SecureFileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get(
    '/surat-masuk/berkas/{attachment}',
    [SecureFileController::class, 'show'],
)->name('incoming-letters.attachments.show');
