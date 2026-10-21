<?php

namespace App\Actions;

use App\Models\Classification;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterNumberGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisterOutgoingLetter
{
    private const MAXIMUM_UPLOAD_BYTES = 10 * 1024 * 1024;

    /** @param array<string, mixed> $data */
    public function execute(User $user, array $data, ?UploadedFile $file = null): OutgoingLetter
    {
        Gate::forUser($user)->authorize('create', OutgoingLetter::class);
        $data = $this->normalizeEmptyValues($data);
        $validated = validator($data, [
            'letter_number' => ['nullable', 'string', 'max:120', Rule::unique('outgoing_letters', 'letter_number')],
            'recipient' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'classification_id' => ['required', 'integer', Rule::exists('classifications', 'id')],
            'letter_date' => ['required', 'date'],
            'sent_date' => ['nullable', 'date'],
        ])->validate();

        $manualNumber = trim((string) ($validated['letter_number'] ?? ''));
        if ($manualNumber !== '' && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['letter_number' => __('outgoing.manual_number_admin_only')]);
        }

        $storedPath = '';

        try {
            return DB::transaction(function () use ($user, $validated, $manualNumber, $file, &$storedPath): OutgoingLetter {
                $classification = Classification::query()->findOrFail($validated['classification_id']);
                $date = Carbon::parse($validated['letter_date']);
                $numbers = app(LetterNumberGenerator::class)->generate(
                    (int) $date->year,
                    (int) $date->month,
                    $classification->code,
                    $manualNumber !== '' ? $manualNumber : null,
                );

                $letter = OutgoingLetter::query()->create(array_merge($validated, $numbers, [
                    'created_by' => $user->getKey(),
                ]));

                if ($file instanceof UploadedFile) {
                    $this->storeFile($letter, $user, $file, $storedPath);
                    activity('outgoing_letter')
                        ->causedBy($user)
                        ->performedOn($letter)
                        ->event('file_uploaded')
                        ->withProperties(['changed_fields' => ['scan']])
                        ->log(__('outgoing.audit_file_uploaded'));
                }

                return $letter->refresh();
            });
        } catch (Throwable $exception) {
            if ($storedPath !== '') {
                Storage::disk('private')->delete($storedPath);
            }

            throw $exception;
        }
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
