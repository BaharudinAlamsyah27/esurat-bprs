<?php

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\ClassificationResource;
use App\Filament\Resources\DispositionInstructionResource;
use App\Filament\Resources\LetterTypeResource;
use App\Filament\Resources\PositionResource;
use App\Filament\Resources\SettingResource;
use App\Filament\Resources\UnitResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\FontScaleWidget;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('applies the resource access matrix to every role', function (string $role, bool $canManage): void {
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    expect(UserResource::canViewAny())->toBe($canManage)
        ->and(UnitResource::canViewAny())->toBe($canManage)
        ->and(PositionResource::canViewAny())->toBe($canManage)
        ->and(ClassificationResource::canViewAny())->toBe($canManage)
        ->and(LetterTypeResource::canViewAny())->toBe($canManage)
        ->and(DispositionInstructionResource::canViewAny())->toBe($canManage)
        ->and(SettingResource::canViewAny())->toBe($canManage);
})->with([
    'super admin' => ['super_admin', true],
    'admin persuratan' => ['admin_persuratan', false],
    'direksi' => ['direksi', false],
    'kepala unit' => ['kepala_unit', false],
    'staf' => ['staf', false],
    'auditor' => ['auditor', false],
]);

it('rejects authentication for an inactive username account', function (): void {
    $user = User::factory()->create(['username' => 'user.nonaktif', 'is_active' => false]);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'user.nonaktif', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('allows an active user to sign in with a username', function (): void {
    $user = User::factory()->create(['username' => 'pengguna.aktif']);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'pengguna.aktif', 'password' => 'password'])
        ->call('authenticate')
        ->assertRedirect('/');

    expect(auth()->id())->toBe($user->id);
});

it('denies a staff user direct access to the user administration route', function (): void {
    $user = User::factory()->create();
    $user->assignRole('staf');

    $this->actingAs($user)->get('/users')->assertForbidden();
});

it('saves the selected font size on the signed-in account', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(FontScaleWidget::class)
        ->call('updateFontScale', 'largest');

    expect($user->refresh()->ui_font_scale)->toBe('largest');
});

it('renders the dashboard and personal profile for an active account', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get('/')->assertOk();
    $this->get('/profil-saya')->assertOk();
});

it('renders user and master data administration screens for a super admin', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get('/users')->assertOk();
    $this->get('/users/create')->assertOk();
    $this->get('/units')->assertOk();
    $this->get('/classifications')->assertOk();
});

it('records master data changes in the activity log', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $this->actingAs($user);

    $unit = Unit::query()->create([
        'name' => 'Operasional',
        'code' => 'OPS',
        'type' => 'divisi',
        'is_active' => true,
    ]);
    $unit->update(['name' => 'Operasional Kantor']);

    expect(Activity::query()
        ->where('subject_type', Unit::class)
        ->where('subject_id', $unit->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});
