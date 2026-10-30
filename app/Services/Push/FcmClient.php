<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmClient
{
    /**
     * Send push notification to a device token using Firebase Cloud Messaging HTTP v1.
     *
     * @param  array{title: string, body: string}  $notification
     * @param  array<string, string>  $data
     */
    public function send(DeviceToken $deviceToken, array $notification, array $data = []): bool
    {
        $enabled = (bool) config('services.fcm.enabled', false);
        $projectId = (string) config('services.fcm.project_id');
        $credentialsPath = base_path((string) config('services.fcm.credentials'));

        if (! $enabled || empty($projectId) || ! file_exists($credentialsPath)) {
            Log::info('FCM push skipped (FCM disabled or credentials not configured)', [
                'device_id' => $deviceToken->id,
                'title' => $notification['title'],
            ]);

            return true;
        }

        $accessToken = $this->getAccessToken($credentialsPath);

        if (! $accessToken) {
            Log::warning('FCM push failed to obtain access token');

            return false;
        }

        $payload = [
            'message' => [
                'token' => $deviceToken->token,
                'notification' => [
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'ttl' => '86400s',
                    'notification' => [
                        'channel_id' => 'disposisi',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                $deviceToken->update(['last_used_at' => now()]);

                return true;
            }

            $error = $response->json('error.status');

            if (in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                Log::info('FCM invalid/unregistered token pruned', ['device_id' => $deviceToken->id]);
                $deviceToken->delete();
            } else {
                Log::warning('FCM message send error', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('FCM push exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Generate an OAuth2 access token from service account credentials JSON.
     */
    protected function getAccessToken(string $credentialsPath): ?string
    {
        try {
            $contents = file_get_contents($credentialsPath);
            if (! $contents) {
                return null;
            }

            $key = json_decode($contents, true);
            if (! is_array($key) || empty($key['client_email']) || empty($key['private_key'])) {
                return null;
            }

            $now = time();
            $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: ''), '+/', '-_'), '=');
            $claim = rtrim(strtr(base64_encode(json_encode([
                'iss' => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]) ?: ''), '+/', '-_'), '=');

            $signature = '';
            $privateKey = openssl_pkey_get_private($key['private_key']);
            if (! $privateKey) {
                return null;
            }

            openssl_sign("{$header}.{$claim}", $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $jwt = "{$header}.{$claim}.".rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $res->json('access_token');
        } catch (\Throwable $e) {
            Log::error('Failed to generate Google access token: '.$e->getMessage());

            return null;
        }
    }
}
