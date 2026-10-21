<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SecureFileController
{
    public function show(Attachment $attachment): Response
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable instanceof IncomingLetter || $attachable instanceof OutgoingLetter, 404);
        Gate::authorize('viewAttachments', $attachable);

        abort_unless($attachment->disk === 'private', 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($attachment->path), 404);

        activity($attachable instanceof IncomingLetter ? 'incoming_letter' : 'outgoing_letter')
            ->causedBy(auth()->user())
            ->event('download_attachment')
            ->withProperties([
                $attachable instanceof IncomingLetter ? 'incoming_letter_id' : 'outgoing_letter_id' => $attachable->getKey(),
                'attachment_id' => $attachment->getKey(),
                'ip' => request()->ip(),
            ])
            ->log(__($attachable instanceof IncomingLetter ? 'incoming.audit_download' : 'outgoing.audit_download'));

        return response()->file($disk->path($attachment->path), [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
