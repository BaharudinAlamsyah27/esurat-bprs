<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns a token when username and password are correct', function (): void {
    $user = User::factory()->create(['username' => 'staf.ops']);

    $response = $this->postJson('/api/login', [
        'username' => 'staf.ops',
        'password' => 'password',
        'device_name' => 'Pest',
    ]);

    $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'username', 'level', 'push_enabled']]);
    expect($user->tokens()->count())->toBe(1)
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects wrong credentials without revealing which field is wrong', function (): void {
    User::factory()->create(['username' => 'staf.ops']);

    $this->postJson('/api/login', [
        'username' => 'staf.ops',
        'password' => 'salah-total',
        'device_name' => 'Pest',
    ])->assertStatus(422)->assertJsonValidationErrors('username');
});

it('refuses login for a deactivated account', function (): void {
    User::factory()->create(['username' => 'nonaktif', 'is_active' => false]);

    $this->postJson('/api/login', [
        'username' => 'nonaktif',
        'password' => 'password',
        'device_name' => 'Pest',
    ])->assertStatus(403);
});

it('blocks an existing token once the account is deactivated', function (): void {
    $user = User::factory()->create(['is_active' => true]);
    Sanctum::actingAs($user);

    $this->getJson('/api/me')->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->getJson('/api/me')->assertStatus(403);
});

it('requires a token to read letters', function (): void {
    $this->getJson('/api/incoming-letters')->assertUnauthorized();
    $this->getJson('/api/outgoing-letters')->assertUnauthorized();
    $this->getJson('/api/dispositions')->assertUnauthorized();
});
