<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class InstitutionLogoController
{
    public function show(): Response
    {
        $path = Institution::query()->whereKey(1)->value('logo_path');
        abort_unless(is_string($path) && $path !== '', 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png'], true), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
