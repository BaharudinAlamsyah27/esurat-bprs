<?php

namespace App\Actions;

use App\Models\Attachment;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateOutgoingLetter
{
    private const MAXIMUM_UPLOAD_BYTES = 10 * 1024 * 1024;

    /** @param array<string, mixed> $data */
    public function execute(User $user, OutgoingLetter $letter, array $data, ?UploadedFile $file = null): OutgoingLetter
    {
        Gate::forUser($user)->authorize('update', $letter);
        $data = $this->normalizeEmptyValues($data);
        $validated = validator($data, [
            'letter_number' => ['nullable', 'string', 'max:120', Rule::unique('outgoing_letters', 'letter_number')->ignore($letter->getKey())],
            'recipient' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'classification_id' => ['required', 'integer', Rule::exists('classifications', 'id')],
            'letter_date' => ['required', 'date'],
            'sent_date' => ['nullable', 'date'],
        ])->validate();

        if (array_key_exists('letter_number', $validated) && filled($validated['letter_number']) && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['letter_number' => __('outgoing.manual_number_admin_only')]);
        }

        $newPath = '';
        $oldPath = null;

        try {
            $updated = DB::transaction(function () use ($user, $letter, $validated, $file, &$newPath, &$oldPath): OutgoingLetter {
                $locked = OutgoingLetter::query()->lockForUpdate()->findOrFail($letter->getKey());
                $dataToSave = $validated;
                if (! filled($dataToSave['letter_number'] ?? null)) {
                    unset($dataToSave['letter_number']);
                }
                $locked->fill($dataToSave)->save();

                if ($file instanceof UploadedFile) {
                    $this->storeFile($locked, $user, $file, $newPath);
                    $old = $locked->attachments()->where('kind', 'final')->first();
                    if ($old instanceof Attachment) {
                        $oldPath = $old->path;
                        $old->delete();
                    }

                    activity('outgoing_letter')
                        ->causedBy($user)
                        ->performedOn($locked)
                        ->event('file_replaced')
                        ->withProperties(['changed_fields' => ['scan']])
                        ->log(__('outgoing.audit_file_replaced'));
                }

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            if ($newPath !== '') {
                Storage::disk('private')->delete($newPath);
            }

            throw $exception;
        }

        if (is_string($oldPath)) {
            Storage::disk('private')->delete($oldPath);
        }

        return $updated;
    }

    private function storeFile(OutgoingLetter $letter, User $user, UploadedFile $file, string &$storedPath): void
    {
        $storedPath = '';
        $mime = $file->getMimeType();
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true) || $file->getSize() > self::MAXIMUM_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['scan' => __('outgoing.invalid_upload')]);
        }

        $temporaryPath = $file->getRealPath();
        $sha256 = is_string($temporaryPath) ? hash_file('sha256', $temporaryPath) : false;
        if (! is_string($sha256)) {
            throw ValidationException::withMessages(['scan' => __('outgoing.invalid_upload')]);
        }

        $path = $file->store('outgoing-letters/'.$letter->agenda_year, 'private');
        if (! is_string($path)) {
            throw ValidationException::withMessages(['scan' => __('outgoing.upload_failed')]);
        }

        $storedPath = $path;

        $letter->attachments()->create([
            'disk' => 'private',
            'path' => $path,
            'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
            'sha256' => $sha256,
            'kind' => 'final',
            'uploaded_by' => $user->getKey(),
        ]);

    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeEmptyValues(array $data): array
    {
        foreach (['letter_number', 'note', 'sent_date'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
