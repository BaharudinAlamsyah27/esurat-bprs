<?php

namespace App\Actions;

use App\Models\Attachment;
use App\Models\CooperationAgreement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaveCooperationAgreement
{
    private const MAXIMUM_UPLOAD_BYTES = 10 * 1024 * 1024;

    /** @param array<string, mixed> $data */
    public function execute(
        User $user,
        array $data,
        ?CooperationAgreement $agreement = null,
        ?UploadedFile $pksFile = null,
        ?UploadedFile $legalityFile = null,
    ): CooperationAgreement {
        $isNew = ! $agreement instanceof CooperationAgreement;

        if ($isNew) {
            Gate::forUser($user)->authorize('create', CooperationAgreement::class);
        } else {
            Gate::forUser($user)->authorize('update', $agreement);
        }

        $data = $this->normalizeEmptyValues($data);

        $validated = validator($data, [
            'bss_pks_number' => ['required', 'string', 'max:120'],
            'partner_pks_number' => ['nullable', 'string', 'max:120'],
            'partner_npwp' => ['nullable', 'string', 'max:50'],
            'agreement_date' => ['required', 'date'],
            'partner_name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'pic_name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        /** @var list<string> $newlyStoredPaths */
        $newlyStoredPaths = [];
        /** @var list<string> $oldPathsToDelete */
        $oldPathsToDelete = [];

        try {
            $saved = DB::transaction(function () use (
                $user,
                $agreement,
                $isNew,
                $validated,
                $pksFile,
                $legalityFile,
                &$newlyStoredPaths,
                &$oldPathsToDelete
            ): CooperationAgreement {
                if ($isNew) {
                    $record = CooperationAgreement::query()->create($validated + [
                        'created_by' => $user->getKey(),
                    ]);
                } else {
                    $record = CooperationAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
                    $record->fill($validated + ['updated_by' => $user->getKey()])->save();
                }

                if ($pksFile instanceof UploadedFile) {
                    $this->replaceAttachment(
                        $record,
                        $user,
                        $pksFile,
                        'pks_file',
                        'pks_file',
                        $newlyStoredPaths,
                        $oldPathsToDelete
                    );
                }

                if ($legalityFile instanceof UploadedFile) {
                    $this->replaceAttachment(
                        $record,
                        $user,
                        $legalityFile,
                        'partner_legality',
                        'partner_legality',
                        $newlyStoredPaths,
                        $oldPathsToDelete
                    );
                }

                if ($isNew) {
                    activity('cooperation_agreement')
                        ->causedBy($user)
                        ->performedOn($record)
                        ->event('created')
                        ->withProperties([
                            'bss_pks_number' => $record->bss_pks_number,
                            'partner_name' => $record->partner_name,
                        ])
                        ->log(__('cooperation.audit_created'));
                }

                return $record->refresh();
            });
        } catch (Throwable $exception) {
            foreach ($newlyStoredPaths as $path) {
                Storage::disk('private')->delete($path);
            }

            throw $exception;
        }

        foreach ($oldPathsToDelete as $oldPath) {
            Storage::disk('private')->delete($oldPath);
        }

        return $saved;
    }

    /**
     * @param  list<string>  $newlyStoredPaths
     * @param  list<string>  $oldPathsToDelete
     */
    private function replaceAttachment(
        CooperationAgreement $record,
        User $user,
        UploadedFile $file,
        string $kind,
        string $errorField,
        array &$newlyStoredPaths,
        array &$oldPathsToDelete,
    ): void {
        $mime = $file->getMimeType();
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true) || $file->getSize() > self::MAXIMUM_UPLOAD_BYTES) {
            throw ValidationException::withMessages([$errorField => __('cooperation.invalid_upload')]);
        }

        $temporaryPath = $file->getRealPath();
        $sha256 = is_string($temporaryPath) ? hash_file('sha256', $temporaryPath) : false;
        if (! is_string($sha256)) {
            throw ValidationException::withMessages([$errorField => __('cooperation.invalid_upload')]);
        }

        $year = Carbon::parse($record->agreement_date)->year;
        $path = $file->store('cooperation-agreements/'.$year, 'private');
        if (! is_string($path)) {
            throw ValidationException::withMessages([$errorField => __('cooperation.upload_failed')]);
        }

        $newlyStoredPaths[] = $path;

        $old = $record->attachments()->where('kind', $kind)->first();
        if ($old instanceof Attachment) {
            $oldPathsToDelete[] = $old->path;
            $old->delete();

            activity('cooperation_agreement')
                ->causedBy($user)
                ->performedOn($record)
                ->event('file_replaced')
                ->withProperties(['changed_fields' => [$kind]])
                ->log(__('cooperation.audit_file_replaced'));
        }

        $record->attachments()->create([
            'disk' => 'private',
            'path' => $path,
            'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
            'sha256' => $sha256,
            'kind' => $kind,
            'uploaded_by' => $user->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeEmptyValues(array $data): array
    {
        foreach (['partner_pks_number', 'partner_npwp', 'note'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
