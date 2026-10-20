<?php

namespace App\Actions;

use App\Enums\DispositionRecipientStatus;
use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Models\Disposition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CloseDispositionManually
{
    public function execute(User $actor, Disposition $disposition, string $reason): Disposition
    {
        Gate::forUser($actor)->authorize('close', $disposition->incomingLetter);
        validator(['reason' => trim($reason)], [
            'reason' => ['required', 'string', 'min:5', 'max:3000'],
        ], [
            'reason.required' => __('dispositions.close_reason_required'),
            'reason.string' => __('dispositions.validation.reason_string'),
            'reason.min' => __('dispositions.validation.reason_min'),
            'reason.max' => __('dispositions.validation.reason_max'),
        ])->validate();

        DB::transaction(function () use ($actor, $disposition, $reason): void {
            $letter = $disposition->incomingLetter()->lockForUpdate()->firstOrFail();
            $letter->dispositions()->with('recipients')->get()->each(function (Disposition $item) use ($actor): void {
                if ($item->status !== DispositionStatus::Completed) {
                    $item->forceFill(['status' => DispositionStatus::Closed, 'closed_at' => now(), 'closed_by' => $actor->id])->save();
                }
                $item->recipients->each(function ($recipient): void {
                    if (! in_array($recipient->status, [DispositionRecipientStatus::Done, DispositionRecipientStatus::Cancelled], true)) {
                        $recipient->forceFill(['status' => DispositionRecipientStatus::Cancelled])->save();
                    }
                });
            });
            $letter->forceFill(['status' => IncomingLetterStatus::Completed, 'completed_at' => now(), 'completion_note' => trim($reason)])->save();
        });

        return $disposition->refresh();
    }
}
