<?php

namespace App\Actions;

use App\Enums\DispositionRecipientStatus;
use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\IncomingLetterUrgency;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDisposition
{
    /** @param array{user_ids?: array<int>, unit_ids?: array<int>, instruction_ids?: array<int>, notes?: ?string, urgency?: string, due_at?: ?string} $data */
    public function execute(User $actor, IncomingLetter $letter, array $data, ?Disposition $parent = null): Disposition
    {
        if ($parent === null && (int) $letter->initial_recipient_id !== (int) $actor->id) {
            $eligibleParents = $letter->dispositions()->whereHas('recipients', function ($query) use ($actor): void {
                $query->whereIn('status', ['pending', 'read', 'in_progress'])->where(function ($recipients) use ($actor): void {
                    $recipients->where('recipient_user_id', $actor->id);
                    if ($actor->unit_id !== null) {
                        $recipients->orWhere('recipient_unit_id', $actor->unit_id);
                    }
                });
            })->get();

            if ($eligibleParents->count() === 1) {
                $parent = $eligibleParents->first();
            } elseif ($eligibleParents->isEmpty()) {
                throw ValidationException::withMessages(['parent_id' => __('dispositions.parent_required_or_unavailable')]);
            } else {
                throw ValidationException::withMessages(['parent_id' => __('dispositions.parent_required')]);
            }
        }

        Gate::forUser($actor)->authorize('create', [Disposition::class, $letter, $parent]);
        $validated = validator($data, [
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')->where('is_active', true)],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', Rule::exists('units', 'id')->where('is_active', true)],
            'instruction_ids' => ['nullable', 'array'],
            'instruction_ids.*' => ['integer', Rule::exists('disposition_instructions', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:3000'],
            'urgency' => ['sometimes', 'string', Rule::enum(IncomingLetterUrgency::class)],
            'due_at' => ['nullable', 'date'],
        ], [
            'user_ids.array' => __('dispositions.validation.user_ids_array'),
            'user_ids.*.integer' => __('dispositions.validation.user_id_integer'),
            'user_ids.*.exists' => __('dispositions.validation.user_id_active'),
            'unit_ids.array' => __('dispositions.validation.unit_ids_array'),
            'unit_ids.*.integer' => __('dispositions.validation.unit_id_integer'),
            'unit_ids.*.exists' => __('dispositions.validation.unit_id_active'),
            'instruction_ids.array' => __('dispositions.validation.instruction_ids_array'),
            'instruction_ids.*.integer' => __('dispositions.validation.instruction_id_integer'),
            'instruction_ids.*.exists' => __('dispositions.validation.instruction_id_active'),
            'notes.string' => __('dispositions.validation.notes_string'),
            'notes.max' => __('dispositions.validation.notes_max'),
            'urgency.string' => __('dispositions.validation.urgency_string'),
            'urgency.enum' => __('dispositions.validation.urgency_invalid'),
            'due_at.date' => __('dispositions.validation.due_at_date'),
        ])->validate();

        $userIds = array_values(array_unique($validated['user_ids'] ?? []));
        $unitIds = array_values(array_unique($validated['unit_ids'] ?? []));
        $instructionIds = array_values(array_unique($validated['instruction_ids'] ?? []));

        if ($userIds === [] && $unitIds === []) {
            throw ValidationException::withMessages(['recipients' => __('dispositions.recipient_required')]);
        }

        if ($userIds !== [] && User::query()->whereIn('id', $userIds)->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['direksi', 'kepala_unit', 'staf']))
            ->count() !== count($userIds)) {
            throw ValidationException::withMessages(['user_ids' => __('dispositions.user_not_eligible')]);
        }

        return DB::transaction(function () use ($actor, $letter, $parent, $validated, $userIds, $unitIds, $instructionIds): Disposition {
            $letter = IncomingLetter::query()->lockForUpdate()->findOrFail($letter->id);
            Gate::forUser($actor)->authorize('create', [Disposition::class, $letter, $parent]);

            if ($parent !== null && ! $parent->recipients()
                ->whereIn('status', ['pending', 'read', 'in_progress'])
                ->where(function ($query) use ($actor): void {
                    $query->where('recipient_user_id', $actor->id);
                    if ($actor->unit_id !== null) {
                        $query->orWhere('recipient_unit_id', $actor->unit_id);
                    }
                })->exists()) {
                throw ValidationException::withMessages(['parent_id' => __('dispositions.parent_not_active_recipient')]);
            }

            $disposition = Disposition::query()->create([
                'incoming_letter_id' => $letter->id,
                'parent_id' => $parent?->id,
                'from_user_id' => $actor->id,
                'notes' => $validated['notes'] ?? null,
                'urgency' => $validated['urgency'] ?? IncomingLetterUrgency::Normal->value,
                'due_at' => $validated['due_at'] ?? null,
                'status' => DispositionStatus::Pending,
            ]);

            foreach ($userIds as $userId) {
                $disposition->recipients()->create(['recipient_user_id' => $userId, 'status' => DispositionRecipientStatus::Pending]);
            }

            foreach ($unitIds as $unitId) {
                $disposition->recipients()->create(['recipient_unit_id' => $unitId, 'status' => DispositionRecipientStatus::Pending]);
            }

            $disposition->instructions()->sync($instructionIds);

            if ($letter->status === IncomingLetterStatus::Forwarded) {
                $letter->forceFill(['status' => IncomingLetterStatus::InDisposition])->save();
            }

            return $disposition->load(['sender', 'recipients.recipient', 'recipients.unit', 'instructions']);
        });
    }
}
