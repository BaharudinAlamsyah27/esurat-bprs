<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\CooperationAgreement;
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
        abort_unless(
            $attachable instanceof IncomingLetter
            || $attachable instanceof OutgoingLetter
            || $attachable instanceof CooperationAgreement,
            404
        );
        Gate::authorize('viewAttachments', $attachable);

        abort_unless($attachment->disk === 'private', 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($attachment->path), 404);

        [$logName, $foreignKey, $logMessage] = match (true) {
            $attachable instanceof IncomingLetter => ['incoming_letter', 'incoming_letter_id', 'incoming.audit_download'],
            $attachable instanceof OutgoingLetter => ['outgoing_letter', 'outgoing_letter_id', 'outgoing.audit_download'],
            default => ['cooperation_agreement', 'cooperation_agreement_id', 'cooperation.audit_download'],
        };

        activity($logName)
            ->causedBy(auth()->user())
            ->event('download_attachment')
            ->withProperties([
                $foreignKey => $attachable->getKey(),
                'attachment_id' => $attachment->getKey(),
                'ip' => request()->ip(),
            ])
            ->log(__($logMessage));

        return response()->file($disk->path($attachment->path), [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
