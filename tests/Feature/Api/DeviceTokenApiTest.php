<?php

use App\Models\DeviceToken;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('registers a push token for the authenticated mobile session', function (): void {
    $user = User::factory()->create();
    $plainToken = $user->createToken('E-Surat Android')->plainTextToken;

    $this->withToken($plainToken)->postJson('/api/device-tokens', [
        'token' => 'fcm-device-token-123',
        'platform' => 'android',
        'device_name' => 'HP UAT',
    ])->assertOk();

    $accessToken = PersonalAccessToken::findToken($plainToken);
    $deviceToken = DeviceToken::query()->where('token', 'fcm-device-token-123')->first();
    expect($deviceToken?->session_id)->toBe('api:'.($accessToken?->getKey()));
});

it('does not allow a user to revoke another users device token', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    DeviceToken::query()->create([
        'user_id' => $owner->id,
        'token' => 'owners-fcm-token',
        'platform' => 'android',
    ]);
    $plainToken = $otherUser->createToken('E-Surat Android')->plainTextToken;

    $this->withToken($plainToken)->deleteJson('/api/device-tokens', [
        'token' => 'owners-fcm-token',
    ])->assertOk();

    expect(DeviceToken::query()->where('token', 'owners-fcm-token')->exists())->toBeTrue();
});

it('revokes the mobile session device token when the user logs out', function (): void {
    $user = User::factory()->create();
    $plainToken = $user->createToken('E-Surat Android')->plainTextToken;
    $accessToken = PersonalAccessToken::findToken($plainToken);
    DeviceToken::query()->create([
        'user_id' => $user->id,
        'token' => 'session-fcm-token',
        'platform' => 'android',
        'session_id' => 'api:'.($accessToken?->getKey()),
    ]);

    $this->withToken($plainToken)->postJson('/api/logout')->assertOk();

    expect(DeviceToken::query()->where('token', 'session-fcm-token')->exists())->toBeFalse()
        ->and(PersonalAccessToken::findToken($plainToken))->toBeNull();
});
