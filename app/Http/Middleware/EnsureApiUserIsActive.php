<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token API tidak otomatis mati ketika Admin menonaktifkan akun.
 * Middleware ini memastikan akun nonaktif langsung kehilangan akses data.
 */
class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            // Hanya token tersimpan di database yang bisa dihapus; saat tes
            // (Sanctum::actingAs) token-nya sementara dan tidak punya delete().
            $token = $user?->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'message' => __('api.inactive_account'),
            ], 403);
        }

        return $next($request);
    }
}
