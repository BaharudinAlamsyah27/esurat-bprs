<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Push\FcmClient;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class FcmChannel
{
    public function __construct(
        protected FcmClient $fcmClient
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            Log::warning('FCM notification skipped (unsupported recipient type)');

            return;
        }

        if (! $notifiable->is_active) {
            Log::info('FCM notification skipped (recipient inactive)');

            return;
        }

        if (! $notifiable->push_enabled) {
            Log::info('FCM notification skipped (push disabled in recipient profile)');

            return;
        }

        if (! method_exists($notification, 'toFcm')) {
            Log::warning('FCM notification skipped (notification has no FCM payload)');

            return;
        }

        /** @var array{notification: array{title: string, body: string}, data?: array<string, string>} $fcmData */
        $fcmData = $notification->toFcm($notifiable);

        $tokens = $notifiable->deviceTokens()->get();

        if ($tokens->isEmpty()) {
            Log::warning('FCM notification skipped (recipient has no registered device token)');

            return;
        }

        foreach ($tokens as $deviceToken) {
            // Token milik aplikasi API hanya aktif selama sesi Bearer terkait
            // masih ada. Ini mencegah push setelah sesi APK dicabut/nonaktif.
            if (str_starts_with((string) $deviceToken->session_id, 'api:')) {
                $apiTokenId = substr((string) $deviceToken->session_id, 4);

                if (! PersonalAccessToken::query()->whereKey($apiTokenId)->exists()) {
                    Log::info('FCM device token removed (API session expired)');
                    $deviceToken->delete();

                    continue;
                }
            }

            $this->fcmClient->send(
                $deviceToken,
                $fcmData['notification'],
                $fcmData['data'] ?? []
            );
        }
    }
}
