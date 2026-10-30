<?php

namespace App\Actions;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class RegisterDeviceToken
{
    /** @param array<string, mixed> $data */
    public function execute(User $user, array $data): DeviceToken
    {
        $validated = Validator::make($data, [
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:20'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $tokenString = $validated['token'];

        $deviceToken = DeviceToken::query()->where('token', $tokenString)->first();

        if ($deviceToken) {
            $deviceToken->update([
                'user_id' => $user->getKey(),
                'platform' => $validated['platform'] ?? 'android',
                'app_version' => $validated['app_version'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
                'last_used_at' => now(),
            ]);
        } else {
            $deviceToken = DeviceToken::query()->create([
                'user_id' => $user->getKey(),
                'token' => $tokenString,
                'platform' => $validated['platform'] ?? 'android',
                'app_version' => $validated['app_version'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
                'last_used_at' => now(),
            ]);
        }

        activity('device')
            ->causedBy($user)
            ->performedOn($deviceToken)
            ->event('registered')
            ->withProperties([
                'platform' => $deviceToken->platform,
                'device_name' => $deviceToken->device_name,
            ])
            ->log(__('m1.audit_device_registered'));

        return $deviceToken;
    }
}
