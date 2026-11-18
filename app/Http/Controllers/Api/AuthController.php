<?php

namespace App\Http\Controllers\Api;

use App\Actions\RevokeDeviceToken;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            activity('authentication')
                ->event('failed_login')
                ->withProperties([
                    'identity' => $request->input('username'),
                    'channel' => 'api',
                    'ip' => $request->ip(),
                ])
                ->log(__('m1.audit_failed_login'));

            throw ValidationException::withMessages([
                'username' => [__('api.invalid_credentials')],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => __('api.inactive_account'),
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        activity('authentication')
            ->causedBy($user)
            ->event('login')
            ->withProperties([
                'channel' => 'api',
                'ip' => $request->ip(),
            ])
            ->log(__('m1.audit_login'));

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'level' => $user->level,
                'position_title' => $user->position_title,
                'push_enabled' => $user->push_enabled,
            ],
        ]);
    }

    public function logout(Request $request, RevokeDeviceToken $revokeDeviceToken): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $deviceTokens = DeviceToken::query()
                ->where('user_id', $user->getKey())
                ->where('session_id', 'api:'.$accessToken->getKey())
                ->pluck('token');

            foreach ($deviceTokens as $deviceToken) {
                $revokeDeviceToken->execute($user, $deviceToken);
            }

            $accessToken->delete();
        }

        activity('authentication')
            ->causedBy($user)
            ->event('logout')
            ->withProperties([
                'channel' => 'api',
                'ip' => $request->ip(),
            ])
            ->log(__('m1.audit_logout'));

        return response()->json([
            'message' => __('api.logged_out'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'level' => $user->level,
            'position_title' => $user->position_title,
            'push_enabled' => $user->push_enabled,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('api.current_password_incorrect')],
            ]);
        }

        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill(['password' => $validated['password']])->save();

            activity('users')
                ->causedBy($user)
                ->performedOn($user)
                ->event('profile_password_changed')
                ->log(__('m1.audit_profile_password_changed'));
        });

        return response()->json([
            'message' => __('api.password_changed'),
        ]);
    }
}
