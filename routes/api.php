<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DispositionController;
use App\Http\Controllers\Api\IncomingLetterController;
use App\Http\Controllers\Api\OutgoingLetterController;
use App\Http\Middleware\EnsureApiUserIsActive;
use Illuminate\Support\Facades\Route;

// Dibatasi agar kata sandi tidak bisa ditebak berulang kali dari aplikasi mobile.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', EnsureApiUserIsActive::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile/password', [AuthController::class, 'changePassword'])->middleware('throttle:5,1');
    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy'])->middleware('throttle:30,1');

    Route::apiResource('incoming-letters', IncomingLetterController::class)->only(['index', 'show']);
    Route::apiResource('outgoing-letters', OutgoingLetterController::class)->only(['index', 'show']);

    Route::get('/dispositions', [DispositionController::class, 'index']);
    Route::get('/dispositions/{disposition}', [DispositionController::class, 'show']);
    Route::post('/dispositions/{disposition}/done', [DispositionController::class, 'markAsDone']);

    // Berkas surat untuk aplikasi mobile (token Bearer, bukan sesi cookie).
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])
        ->name('api.attachments.show');
});
