<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterDeviceToken;
use App\Actions\RevokeDeviceToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceTokenController extends Controller
{
    public function store(Request $request, RegisterDeviceToken $action): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'in:android'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $accessToken = $user->currentAccessToken();
        $validated['session_id'] = $accessToken instanceof PersonalAccessToken
            ? 'api:'.$accessToken->getKey()
            : null;

        $deviceToken = $action->execute($user, $validated);

        return response()->json([
            'message' => __('api.device_registered'),
            'id' => $deviceToken->getKey(),
        ]);
    }

    public function destroy(Request $request, RevokeDeviceToken $action): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $revoked = $action->execute($user, $validated['token']);

        return response()->json([
            'message' => $revoked ? __('api.device_revoked') : __('api.device_not_found'),
        ]);
    }
}
