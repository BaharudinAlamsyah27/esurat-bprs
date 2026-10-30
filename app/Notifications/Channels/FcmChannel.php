<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Push\FcmClient;
use Illuminate\Notifications\Notification;

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
            $this->fcmClient->send(
                $deviceToken,
                $fcmData['notification'],
                $fcmData['data'] ?? []
            );
        }
    }
}
