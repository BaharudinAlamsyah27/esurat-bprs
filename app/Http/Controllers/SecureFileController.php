<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\IncomingLetter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SecureFileController
{
    public function show(Attachment $attachment): Response
    {
        abort_unless($attachment->attachable instanceof IncomingLetter, 404);

        /** @var IncomingLetter $letter */
        $letter = $attachment->attachable;
        Gate::authorize('viewAttachments', $letter);

        abort_unless($attachment->disk === 'private', 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($attachment->path), 404);

        activity('incoming_letter')
            ->causedBy(auth()->user())
            ->event('download_attachment')
            ->withProperties([
                'incoming_letter_id' => $letter->getKey(),
                'attachment_id' => $attachment->getKey(),
                'ip' => request()->ip(),
            ])
            ->log(__('incoming.audit_download'));

        return response()->file($disk->path($attachment->path), [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
