<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SecureFileController
{
    public function show(Attachment $attachment): Response
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable instanceof IncomingLetter || $attachable instanceof Disposition, 404);

        if ($attachable instanceof IncomingLetter) {
            Gate::authorize('viewAttachments', $attachable);
        } else {
            Gate::authorize('view', $attachable);
        }

        abort_unless($attachment->disk === 'private', 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($attachment->path), 404);

        activity($attachable instanceof IncomingLetter ? 'incoming_letter' : 'disposition')
            ->causedBy(auth()->user())
            ->event('download_attachment')
            ->withProperties([
                'incoming_letter_id' => $attachable instanceof IncomingLetter ? $attachable->getKey() : $attachable->incoming_letter_id,
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
