<?php

namespace App\Http\Controllers;

use App\Actions\RegisterDeviceToken;
use App\Actions\RevokeDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request, RegisterDeviceToken $action): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:20'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $data['session_id'] = $request->session()->getId();

        $deviceToken = $action->execute($user, $data);

        return response()->json([
            'message' => 'Device token registered successfully.',
            'id' => $deviceToken->id,
        ]);
    }

    public function destroy(Request $request, RevokeDeviceToken $action): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $revoked = $action->execute($user, $data['token']);

        return response()->json([
            'message' => $revoked ? 'Device token revoked successfully.' : 'Token not found.',
        ]);
    }
}
