<?php

namespace App\Actions;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\LetterSequence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisterIncomingLetter
{
    private const MAXIMUM_UPLOAD_BYTES = 10 * 1024 * 1024;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function execute(User $user, array $data, array $files = []): IncomingLetter
    {
        Gate::forUser($user)->authorize('create', IncomingLetter::class);

        $year = now()->year;
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($user, $data, $files, $year, &$storedPaths): IncomingLetter {
                $now = now();

                DB::table('letter_sequences')->insertOrIgnore([
                    'category' => 'incoming_agenda',
                    'scope_key' => (string) $year,
                    'last_number' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $sequence = LetterSequence::query()
                    ->where('category', 'incoming_agenda')
                    ->where('scope_key', (string) $year)
                    ->lockForUpdate()
                    ->firstOrFail();

                $sequence->increment('last_number');
                $agendaNumber = str_pad((string) $sequence->last_number, 4, '0', STR_PAD_LEFT).'/'.$year;

                $letter = IncomingLetter::query()->create(array_merge($data, [
                    'agenda_year' => $year,
                    'agenda_seq' => $sequence->last_number,
                    'agenda_number' => $agendaNumber,
                    'attachments_count' => 0,
                    'status' => IncomingLetterStatus::Registered->value,
                    'registered_by' => $user->getKey(),
                ]));

                foreach ($files as $file) {
                    $this->storeAttachment($letter, $user, $file, $storedPaths);
                }

                $letter->forceFill(['attachments_count' => count($storedPaths)])->save();

                return $letter->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($storedPaths);

            throw $exception;
        }
    }

    /** @param array<int, string> $storedPaths */
    private function storeAttachment(IncomingLetter $letter, User $user, UploadedFile $file, array &$storedPaths): void
    {
        $mime = $file->getMimeType();
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (! in_array($mime, $allowedMimes, true) || $file->getSize() > self::MAXIMUM_UPLOAD_BYTES) {
            throw ValidationException::withMessages([
                'data.scans' => __('incoming.invalid_upload'),
            ]);
        }

        $temporaryPath = $file->getRealPath();
        $sha256 = is_string($temporaryPath) ? hash_file('sha256', $temporaryPath) : false;

        if (! is_string($sha256)) {
            throw ValidationException::withMessages([
                'data.scans' => __('incoming.invalid_upload'),
            ]);
        }

        $path = $file->store('incoming-scans/'.$letter->agenda_year, 'private');

        if (! is_string($path)) {
            throw ValidationException::withMessages([
                'data.scans' => __('incoming.upload_failed'),
            ]);
        }

        $storedPaths[] = $path;

        $letter->attachments()->create([
            'disk' => 'private',
            'path' => $path,
            'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
            'sha256' => $sha256,
            'kind' => 'scan',
            'uploaded_by' => $user->getKey(),
        ]);
    }
}
