<?php

namespace App\Actions;

use App\Models\DeviceToken;
use App\Models\User;

class RevokeDeviceToken
{
    public function execute(User $user, string $token): bool
    {
        $deviceToken = DeviceToken::query()
            ->where('user_id', $user->getKey())
            ->where('token', $token)
            ->first();

        if (! $deviceToken) {
            return false;
        }

        $deviceToken->delete();

        activity('device')
            ->causedBy($user)
            ->event('revoked')
            ->withProperties([
                'platform' => $deviceToken->platform,
                'device_name' => $deviceToken->device_name,
            ])
            ->log(__('m1.audit_device_revoked'));

        return true;
    }
}
