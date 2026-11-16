<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CooperationAgreement;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyaji berkas surat untuk aplikasi mobile.
 *
 * Route web hanya mengenali sesi cookie, sedangkan aplikasi mobile mengirim
 * token Bearer. Controller ini memakai guard sanctum dan tetap menegakkan
 * Policy serta mencatat audit unduhan.
 */
class AttachmentController extends Controller
{
    public function show(Request $request, Attachment $attachment): Response
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
            ->causedBy($request->user())
            ->event('download_attachment')
            ->withProperties([
                $foreignKey => $attachable->getKey(),
                'attachment_id' => $attachment->getKey(),
                'channel' => 'api',
                'ip' => $request->ip(),
            ])
            ->log(__($logMessage));

        return response()->file($disk->path($attachment->path), [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
