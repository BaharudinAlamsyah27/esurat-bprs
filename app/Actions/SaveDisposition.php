<?php

namespace App\Actions;

use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Enums\DispositionRecipientStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Notifications\DispositionAssigned;
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
            'recipient_user_ids' => ['nullable', 'array'],
            'recipient_user_ids.*' => ['integer', 'exists:users,id'],
            'other_recipients' => ['nullable', 'string', 'max:1000'],
            'forwarded_to_input' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['required', 'array', 'min:1'],
            'instructions.*' => ['required', Rule::enum(DispositionInstruction::class)],
            'instruction_other' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'disposition_date' => ['required', 'date'],
        ])->validate();

        $recipientUserIds = array_map('intval', (array) ($validated['recipient_user_ids'] ?? []));
        $otherRecipients = trim((string) ($validated['other_recipients'] ?? ''));
        $forwardedInput = trim((string) ($validated['forwarded_to_input'] ?? ''));

        // If recipient_user_ids given, build destinations from user models + other
        $destinations = [];
        $recipientUsers = [];
        if (! empty($recipientUserIds)) {
            $recipientUsers = User::query()->whereIn('id', $recipientUserIds)->get();
            foreach ($recipientUsers as $recUser) {
                $label = $recUser->name;
                if (filled($recUser->position_title)) {
                    $label .= ' ('.$recUser->position_title.')';
                }
                $destinations[] = $label;
            }
            if ($otherRecipients !== '') {
                $extra = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $otherRecipients) ?: [])));
                $destinations = array_merge($destinations, $extra);
            }
        } elseif ($forwardedInput !== '') {
            $destinations = array_values(array_filter(array_map(
                static fn (string $name): string => trim($name),
                preg_split('/\r\n|\r|\n/', $forwardedInput) ?: [],
            )));
        }

        if ($destinations === [] && empty($recipientUserIds) && $otherRecipients === '') {
            throw ValidationException::withMessages([
                'recipient_user_ids' => __('disposition.destination_required'),
                'forwarded_to_input' => __('disposition.destination_required'),
            ]);
        }

        if (in_array(DispositionInstruction::Lainnya->value, $validated['instructions'], true) && blank($validated['instruction_other'])) {
            throw ValidationException::withMessages(['instruction_other' => __('disposition.other_required')]);
        }

        return DB::transaction(function () use ($user, $letter, $validated, $destinations, $recipientUserIds): Disposition {
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

            // Sync recipients if recipient_user_ids provided
            if (! empty($recipientUserIds) || isset($validated['recipient_user_ids'])) {
                $currentRecipients = $disposition->recipients()->get();
                $currentMap = $currentRecipients->keyBy('user_id');

                // Remove recipients not in new list, unless status is 'done'
                foreach ($currentRecipients as $curr) {
                    if (! in_array($curr->user_id, $recipientUserIds, true)) {
                        if ($curr->status !== DispositionRecipientStatus::Done) {
                            $curr->delete();
                        }
                    }
                }

                // Add newly assigned recipients
                foreach ($recipientUserIds as $uId) {
                    if (! $currentMap->has($uId)) {
                        $newRec = $disposition->recipients()->create([
                            'user_id' => $uId,
                            'status' => DispositionRecipientStatus::New,
                            'notified_at' => now(),
                        ]);

                        $assignedUser = User::query()->find($uId);
                        if ($assignedUser instanceof User) {
                            $assignedUser->notify(new DispositionAssigned($newRec));
                        }
                    }
                }
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
