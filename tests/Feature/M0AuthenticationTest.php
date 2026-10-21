<?php

use App\Enums\UserLevel;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('allows an active admin to sign in with username', function (): void {
    $admin = User::factory()->create([
        'username' => 'adminuji',
        'level' => UserLevel::Admin,
        'password' => 'KataSandiUji123',
    ]);

    Livewire::test(Login::class)
        ->fillForm(['username' => 'adminuji', 'password' => 'KataSandiUji123'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($admin);
});

it('allows an active user to sign in with username', function (): void {
    $user = User::factory()->create([
        'username' => 'penggunauji',
        'level' => UserLevel::User,
        'password' => 'KataSandiUji123',
    ]);

    Livewire::test(Login::class)
        ->fillForm(['username' => 'penggunauji', 'password' => 'KataSandiUji123'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
});

it('rejects inactive users even when their password is correct', function (): void {
    User::factory()->create([
        'username' => 'nonaktif',
        'is_active' => false,
        'password' => 'KataSandiUji123',
    ]);

    Livewire::test(Login::class)
        ->fillForm(['username' => 'nonaktif', 'password' => 'KataSandiUji123'])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);

    $this->assertGuest();
});

it('identifies only the admin level as admin', function (): void {
    expect(User::factory()->create(['level' => UserLevel::Admin])->isAdmin())->toBeTrue()
        ->and(User::factory()->create(['level' => UserLevel::User])->isAdmin())->toBeFalse();
});
