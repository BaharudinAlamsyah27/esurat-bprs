<?php

namespace App\Models\Concerns;

use App\Enums\LetterConfidentiality;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class VisibleToUserScope implements Scope
{
    /** @param Builder<Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $isAdmin = $user->hasRole('admin_persuratan');
        $isDirector = $user->hasRole('direksi');
        $isAuditor = $user->hasRole('auditor');
        $isUnitHead = $user->hasRole('kepala_unit') && $user->unit_id !== null;
        $userId = $user->getKey();
        $unitId = $user->unit_id;

        $builder->where(function (Builder $query) use ($isAdmin, $isDirector, $isAuditor, $isUnitHead, $userId, $unitId): void {
            $query->where(function (Builder $ordinary) use ($isAdmin, $isDirector, $isAuditor, $isUnitHead, $userId, $unitId): void {
                $ordinary->where('confidentiality', LetterConfidentiality::Ordinary->value)
                    ->where(function (Builder $visible) use ($isAdmin, $isDirector, $isAuditor, $isUnitHead, $userId, $unitId): void {
                        if ($isAdmin || $isDirector || $isAuditor) {
                            $visible->orWhereRaw('1 = 1');
                        }

                        $this->includeDirectInvolvement($visible, $userId);

                        if ($isUnitHead) {
                            $this->includeRelatedUnit($visible, $unitId);
                        }
                    });
            });

            $query->orWhere(function (Builder $restricted) use ($isAdmin, $isDirector, $isUnitHead, $userId, $unitId): void {
                $restricted->where('confidentiality', LetterConfidentiality::Restricted->value)
                    ->where(function (Builder $visible) use ($isAdmin, $isDirector, $isUnitHead, $userId, $unitId): void {
                        if ($isAdmin || $isDirector) {
                            $visible->orWhereRaw('1 = 1');
                        }

                        $this->includeDirectInvolvement($visible, $userId);

                        if ($isUnitHead) {
                            $this->includeRelatedUnit($visible, $unitId);
                        }
                    });
            });

            $query->orWhere(function (Builder $secret) use ($isAdmin, $isDirector, $userId): void {
                $secret->where('confidentiality', LetterConfidentiality::Secret->value)
                    ->where(function (Builder $visible) use ($isAdmin, $isDirector, $userId): void {
                        if ($isAdmin || $isDirector) {
                            $visible->orWhereRaw('1 = 1');
                        }

                        $this->includeDirectInvolvement($visible, $userId);
                    });
            });
        });
    }

    public static function canSee(User $user, IncomingLetter $letter): bool
    {
        $directlyInvolved = $letter->isDirectlyInvolved($user);
        $inDisposition = $letter->dispositions()->where(function (Builder $query) use ($user): void {
            $query->where('from_user_id', $user->id)
                ->orWhereHas('recipients', function (Builder $recipients) use ($user): void {
                    $recipients->where('recipient_user_id', $user->id);
                    if ($user->unit_id !== null) {
                        $recipients->orWhere('recipient_unit_id', $user->unit_id);
                    }
                });
        })->exists();
        $isAdmin = $user->hasRole('admin_persuratan');
        $isDirector = $user->hasRole('direksi');
        $isAuditor = $user->hasRole('auditor');
        $isUnitHead = $user->hasRole('kepala_unit')
            && $user->unit_id !== null
            && $letter->initialRecipient()->where('unit_id', $user->unit_id)->exists();

        return match ($letter->confidentiality) {
            LetterConfidentiality::Ordinary => $isAdmin || $isDirector || $isAuditor || $directlyInvolved || $inDisposition || $isUnitHead,
            LetterConfidentiality::Restricted => $isAdmin || $isDirector || $directlyInvolved || $inDisposition || $isUnitHead,
            LetterConfidentiality::Secret => $isAdmin || $isDirector || $directlyInvolved || $inDisposition,
        };
    }

    /** @param Builder<Model> $query */
    private function includeDirectInvolvement(Builder $query, int|string $userId): void
    {
        $query->orWhere('registered_by', $userId)
            ->orWhere('initial_recipient_id', $userId);
        $this->includeDispositionInvolvement($query, $userId);
    }

    /** @param Builder<Model> $query */
    private function includeDispositionInvolvement(Builder $query, int|string $userId): void
    {
        $unitId = auth()->user()?->unit_id;
        $query->orWhereHas('dispositions', fn (Builder $dispositions): Builder => $dispositions
            ->where('from_user_id', $userId)
            ->orWhereHas('recipients', function (Builder $recipients) use ($userId, $unitId): void {
                $recipients->where('recipient_user_id', $userId);
                if ($unitId !== null) {
                    $recipients->orWhere('recipient_unit_id', $unitId);
                }
            }));
    }

    /** @param Builder<Model> $query */
    private function includeRelatedUnit(Builder $query, int|string|null $unitId): void
    {
        $query->orWhereHas('initialRecipient', fn (Builder $recipient): Builder => $recipient->where('unit_id', $unitId));
    }
}
