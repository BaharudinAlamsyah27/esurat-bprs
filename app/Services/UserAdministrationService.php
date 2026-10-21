<?php

namespace App\Services;

use App\Enums\UserLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserAdministrationService
{
    public function toggleActive(User $actor, User $target): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        if ($actor->is($target)) {
            throw ValidationException::withMessages(['record' => __('m1.cannot_deactivate_self')]);
        }

        DB::transaction(function () use ($actor, $target): void {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $activating = ! $lockedTarget->is_active;

            if (! $activating && $lockedTarget->isAdmin()) {
                $activeAdminIds = User::query()
                    ->where('level', UserLevel::Admin->value)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->pluck('id');

                if ($activeAdminIds->count() <= 1) {
                    throw ValidationException::withMessages(['record' => __('m1.last_active_admin')]);
                }
            }

            $lockedTarget->forceFill(['is_active' => $activating])->save();

            activity('users')
                ->causedBy($actor)
                ->performedOn($lockedTarget)
                ->event($activating ? 'user_activated' : 'user_deactivated')
                ->log($activating ? __('m1.audit_user_activated') : __('m1.audit_user_deactivated'));
        });
    }

    public function resetPassword(User $actor, User $target, string $password): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        if ($actor->is($target)) {
            throw ValidationException::withMessages(['record' => __('m1.use_profile_to_change_password')]);
        }

        DB::transaction(function () use ($actor, $target, $password): void {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $lockedTarget->forceFill(['password' => $password])->save();

            activity('users')
                ->causedBy($actor)
                ->performedOn($lockedTarget)
                ->event('password_reset')
                ->log(__('m1.audit_password_reset'));
        });
    }
}
