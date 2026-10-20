<?php

namespace App\Services;

use App\Enums\DispositionRecipientStatus;
use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use Illuminate\Support\Facades\DB;

class DispositionService
{
    public function refreshLetterStatus(IncomingLetter $letter): void
    {
        DB::transaction(function () use ($letter): void {
            $letter = IncomingLetter::query()->lockForUpdate()->findOrFail($letter->id);
            $recipients = $letter->dispositions()->with('recipients')->get()->flatMap(fn (Disposition $disposition) => $disposition->recipients);
            $active = $recipients
                ->pluck('status')
                ->map(fn (DispositionRecipientStatus|string $status): string => $status instanceof DispositionRecipientStatus ? $status->value : $status)
                ->reject(fn (string $status): bool => $status === DispositionRecipientStatus::Cancelled->value);

            if ($active->isNotEmpty() && $active->every(fn (string $status): bool => $status === DispositionRecipientStatus::Done->value)) {
                $letter->forceFill(['status' => IncomingLetterStatus::Completed, 'completed_at' => now()])->save();
            } elseif ($active->contains(DispositionRecipientStatus::InProgress->value)) {
                $letter->forceFill(['status' => IncomingLetterStatus::InProgress, 'completed_at' => null])->save();
            }
        });
    }

    public function refreshDisposition(Disposition $disposition): void
    {
        DB::transaction(function () use ($disposition): void {
            $disposition = Disposition::query()->lockForUpdate()->findOrFail($disposition->id);
            if ($disposition->status === DispositionStatus::Closed) {
                return;
            }

            $statuses = $disposition->recipients()
                ->where('status', '!=', DispositionRecipientStatus::Cancelled->value)
                ->get(['status'])
                ->pluck('status')
                ->map(fn (DispositionRecipientStatus|string $value): string => $value instanceof DispositionRecipientStatus ? $value->value : $value);
            $status = $statuses->isNotEmpty() && $statuses->every(fn (string $value): bool => $value === DispositionRecipientStatus::Done->value)
                ? DispositionStatus::Completed
                : ($statuses->contains(DispositionRecipientStatus::InProgress->value) ? DispositionStatus::InProgress : DispositionStatus::Pending);

            $disposition->forceFill(['status' => $status])->save();
        });
        $this->refreshLetterStatus($disposition->incomingLetter);
    }
}
