<?php

namespace App\Listeners;

use App\Models\DeviceToken;
use Illuminate\Auth\Events\Logout;

class RemoveDeviceTokensOnLogout
{
    public function handle(Logout $event): void
    {
        $sessionId = session()->getId();

        if (filled($sessionId)) {
            DeviceToken::query()
                ->where('session_id', $sessionId)
                ->delete();
        }
    }
}
