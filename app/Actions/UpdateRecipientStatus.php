<?php

namespace App\Actions;

use App\Enums\DispositionRecipientStatus;
use App\Models\DispositionRecipient;
use App\Models\User;
use App\Services\DispositionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateRecipientStatus
{
    public function execute(User $actor, DispositionRecipient $recipient, DispositionRecipientStatus $status, ?string $reportNotes = null, ?UploadedFile $file = null): DispositionRecipient
    {
        Gate::forUser($actor)->authorize('update', $recipient);
        validator(['report_notes' => $reportNotes], [
            'report_notes' => ['nullable', 'string', 'max:3000'],
        ], [
            'report_notes.string' => __('dispositions.validation.report_string'),
            'report_notes.max' => __('dispositions.validation.report_max'),
        ])->validate();

        if ($file !== null && (! in_array($file->getMimeType(), ['application/pdf', 'image/jpeg', 'image/png'], true) || $file->getSize() > 10 * 1024 * 1024)) {
            throw ValidationException::withMessages(['report_file' => __('dispositions.upload_invalid')]);
        }
        $path = null;

        try {
            if ($file !== null) {
                $path = $file->store('disposition-reports/'.$recipient->disposition_id, 'private');
                if ($path === false) {
                    throw ValidationException::withMessages(['report_file' => __('dispositions.upload_failed')]);
                }
            }

            return DB::transaction(function () use ($actor, $recipient, $status, $reportNotes, $file, &$path): DispositionRecipient {
                $recipient = DispositionRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
                $allowed = match ($recipient->status) {
                    DispositionRecipientStatus::Pending => [DispositionRecipientStatus::Read],
                    DispositionRecipientStatus::Read => [DispositionRecipientStatus::InProgress],
                    DispositionRecipientStatus::InProgress => [DispositionRecipientStatus::Done],
                    default => [],
                };

                if (! in_array($status, $allowed, true)) {
                    throw ValidationException::withMessages(['status' => __('dispositions.invalid_transition')]);
                }
                if ($status === DispositionRecipientStatus::Done && blank($reportNotes)) {
                    throw ValidationException::withMessages(['report_notes' => __('dispositions.report_required')]);
                }

                $updates = ['status' => $status, 'report_notes' => $reportNotes];
                if ($status === DispositionRecipientStatus::Read) {
                    $updates['read_at'] = now();
                } elseif ($status === DispositionRecipientStatus::InProgress) {
                    $updates['started_at'] = $recipient->started_at ?? now();
                } elseif ($status === DispositionRecipientStatus::Done) {
                    $updates['done_at'] = now();
                }
                $recipient->forceFill($updates)->save();

                if ($file !== null && is_string($path)) {
                    $sha256 = hash_file('sha256', Storage::disk('private')->path($path));
                    if ($sha256 === false) {
                        throw ValidationException::withMessages(['report_file' => __('dispositions.upload_failed')]);
                    }
                    $recipient->disposition->attachments()->create([
                        'disk' => 'private', 'path' => $path, 'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize(),
                        'sha256' => $sha256, 'kind' => 'laporan', 'uploaded_by' => $actor->id,
                    ]);
                }

                app(DispositionService::class)->refreshDisposition($recipient->disposition);

                return $recipient->refresh();
            });
        } catch (\Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('private')->delete($path);
            }

            throw $exception;
        }
    }
}
