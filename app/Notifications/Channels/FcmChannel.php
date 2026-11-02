<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Push\FcmClient;
use Illuminate\Notifications\Notification;
use Laravel\Sanctum\PersonalAccessToken;

class FcmChannel
{
    public function __construct(
        protected FcmClient $fcmClient
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! $notifiable->is_active || ! $notifiable->push_enabled) {
            return;
        }

        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        /** @var array{notification: array{title: string, body: string}, data?: array<string, string>} $fcmData */
        $fcmData = $notification->toFcm($notifiable);

        $tokens = $notifiable->deviceTokens()->get();

        foreach ($tokens as $deviceToken) {
            // Token milik aplikasi API hanya aktif selama sesi Bearer terkait
            // masih ada. Ini mencegah push setelah sesi APK dicabut/nonaktif.
            if (str_starts_with((string) $deviceToken->session_id, 'api:')) {
                $apiTokenId = substr((string) $deviceToken->session_id, 4);

                if (! PersonalAccessToken::query()->whereKey($apiTokenId)->exists()) {
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
