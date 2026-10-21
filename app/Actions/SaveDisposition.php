<?php

namespace App\Actions;

use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveDisposition
{
    /** @param array<string, mixed> $data */
    public function execute(User $user, IncomingLetter $letter, array $data): Disposition
    {
        $existing = $letter->disposition;

        if ($existing instanceof Disposition) {
            Gate::forUser($user)->authorize('update', $existing);
        } else {
            Gate::forUser($user)->authorize('create', Disposition::class);
        }

        $validated = Validator::make($data, [
            'nature' => ['required', Rule::enum(DispositionNature::class)],
            'forwarded_to_input' => ['required', 'string', 'max:2000'],
            'instructions' => ['required', 'array', 'min:1'],
            'instructions.*' => ['required', Rule::enum(DispositionInstruction::class)],
            'instruction_other' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'disposition_date' => ['required', 'date'],
        ])->validate();

        $destinations = array_values(array_filter(array_map(
            static fn (string $name): string => trim($name),
            preg_split('/\r\n|\r|\n/', $validated['forwarded_to_input']) ?: [],
        )));

        if ($destinations === []) {
            throw ValidationException::withMessages(['forwarded_to_input' => __('disposition.destination_required')]);
        }

        if (in_array(DispositionInstruction::Lainnya->value, $validated['instructions'], true) && blank($validated['instruction_other'])) {
            throw ValidationException::withMessages(['instruction_other' => __('disposition.other_required')]);
        }

        return DB::transaction(function () use ($user, $letter, $validated, $destinations): Disposition {
            IncomingLetter::query()->whereKey($letter->getKey())->lockForUpdate()->firstOrFail();
            $disposition = $letter->disposition()->lockForUpdate()->first();
            $isNew = ! $disposition instanceof Disposition;

            $fields = [
                'nature' => $validated['nature'],
                'forwarded_to' => $destinations,
                'instructions' => $validated['instructions'],
                'instruction_other' => $validated['instruction_other'] ?? null,
                'note' => $validated['note'] ?? null,
                'disposition_date' => $validated['disposition_date'],
                'updated_by' => $user->getKey(),
            ];

            if ($isNew) {
                $disposition = $letter->disposition()->create($fields + [
                    'created_by' => $user->getKey(),
                    'print_count' => 0,
                ]);
            } else {
                $disposition->fill($fields)->save();
            }

            activity('disposition')
                ->causedBy($user)
                ->performedOn($disposition)
                ->event($isNew ? 'created' : 'updated')
                ->withProperties([
                    'incoming_letter_id' => $letter->getKey(),
                    'changed_fields' => array_keys($fields),
                ])
                ->log(__($isNew ? 'disposition.audit_created' : 'disposition.audit_updated'));

            return $disposition->refresh();
        });
    }
}
