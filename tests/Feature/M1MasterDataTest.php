<?php

use App\Enums\UserLevel;
use App\Filament\Pages\InstitutionSettings;
use App\Filament\Pages\Profile;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ClassificationResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Support\ThemePalette;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use App\Services\UserAdministrationService;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('seeds the nine standard classifications and one institution record', function (): void {
    $this->seed(MasterDataSeeder::class);
    $this->seed(MasterDataSeeder::class);

    expect(Classification::query()->count())->toBe(9)
        ->and(Institution::query()->count())->toBe(1)
        ->and(Institution::query()->first()->name)->toBe('BPRS Sragen');
});

it('allows admins to update the singleton institution and records the change', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $this->actingAs($admin);

    Livewire::test(InstitutionSettings::class)
        ->fillForm([
            'name' => 'BPRS Sragen',
            'address' => 'Alamat contoh',
            'phone' => '0271-000000',
            'email' => 'surat@example.test',
            'city' => 'Sragen',
            'leader_name' => 'Pimpinan Contoh',
            'leader_position' => 'Direktur Utama',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Institution::query()->count())->toBe(1)
        ->and(Institution::query()->first()->leader_name)->toBe('Pimpinan Contoh');

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Institution::class,
        'subject_id' => 1,
    ]);
});

it('defaults to the BPRS green accent when no theme has been selected', function (): void {
    expect(ThemePalette::selected())->toBe('green');
});

it('offers and saves each supported accent theme for all users', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $this->actingAs($admin);

    expect(ThemePalette::options())->toBe([
        'green' => __('m1.theme_green'),
        'blue' => __('m1.theme_blue'),
        'maroon' => __('m1.theme_maroon'),
        'teal' => __('m1.theme_teal'),
    ]);

    foreach (array_keys(ThemePalette::options()) as $theme) {
        Livewire::test(InstitutionSettings::class)
            ->set('data.theme_color', $theme)
            ->call('save')
            ->assertHasNoFormErrors();

        expect(ThemePalette::selected())->toBe($theme)
            ->and(Setting::query()->where('key', 'ui_theme_color')->first()?->value['color'])->toBe($theme);
    }
});

it('denies non-admin users from changing the accent theme', function (): void {
    $user = User::factory()->create(['level' => UserLevel::User]);
    $this->actingAs($user);

    $this->get(InstitutionSettings::getUrl())->assertForbidden();

    expect(ThemePalette::selected())->toBe('green');
});

it('hides and denies master data pages to regular users', function (): void {
    $user = User::factory()->create(['level' => UserLevel::User]);
    $this->actingAs($user);

    expect(ClassificationResource::canViewAny())->toBeFalse()
        ->and(UserResource::canViewAny())->toBeFalse()
        ->and(ActivityLogResource::canViewAny())->toBeFalse();

    $this->get(InstitutionSettings::getUrl())->assertForbidden();
    $this->get(ClassificationResource::getUrl())->assertForbidden();
    $this->get(UserResource::getUrl())->assertForbidden();
    $this->get(ActivityLogResource::getUrl())->assertForbidden();
});

it('serves the institution logo only to signed-in users from private storage', function (): void {
    Storage::fake('private');
    $logoPath = 'institution/logos/test.png';
    Storage::disk('private')->put($logoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j4ZkAAAAASUVORK5CYII='));
    Institution::query()->create(['id' => 1, 'name' => 'BPRS Sragen', 'logo_path' => $logoPath]);

    $this->get(route('institution.logo'))->assertRedirect();

    $this->actingAs(User::factory()->create(['level' => UserLevel::User]))
        ->get(route('institution.logo'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('prevents deletion of a classification that is already used by incoming mail', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $classification = Classification::factory()->create();
    IncomingLetter::factory()->create([
        'classification_id' => $classification->getKey(),
        'registered_by' => $admin->getKey(),
    ]);

    expect($admin->can('delete', $classification))->toBeFalse();
});

it('protects the last active admin and prevents self deletion', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin, 'is_active' => true]);
    $otherAdmin = User::factory()->create(['level' => UserLevel::Admin, 'is_active' => false]);

    expect($admin->can('delete', $admin))->toBeFalse()
        ->and($admin->can('delete', $otherAdmin))->toBeTrue();
});

it('does not treat account creation as user activity history', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin, 'is_active' => true]);
    $otherUser = User::factory()->create(['level' => UserLevel::User]);

    expect($admin->can('delete', $otherUser))->toBeTrue();
});

it('prevents deleting a user who has performed an audited action', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin, 'is_active' => true]);
    $otherUser = User::factory()->create(['level' => UserLevel::User]);

    activity('users')->causedBy($otherUser)->event('test_action')->log('Aksi uji');

    expect($admin->can('delete', $otherUser))->toBeFalse();
});

it('prevents deactivating the last active admin', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin, 'is_active' => true]);
    $otherAdmin = User::factory()->create(['level' => UserLevel::Admin, 'is_active' => false]);

    expect(fn () => app(UserAdministrationService::class)->toggleActive($admin, $admin))
        ->toThrow(ValidationException::class);

    expect($admin->fresh()->is_active)->toBeTrue();
});

it('does not expose password fields on the user edit form', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $target = User::factory()->create(['name' => 'Pengguna Contoh']);
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->assertDontSee('type="password"');
});

it('creates a user with a hashed password and without showing the password in the listing', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Pegawai Contoh',
            'username' => 'pegawaicontoh',
            'email' => 'pegawai@example.test',
            'level' => UserLevel::User->value,
            'password' => 'KataSandiBaru123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $createdUser = User::query()->where('username', 'pegawaicontoh')->firstOrFail();
    expect(Hash::check('KataSandiBaru123', $createdUser->password))->toBeTrue()
        ->and($createdUser->is_active)->toBeTrue();
});

it('lets a user update their profile and change password with the current password', function (): void {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'password' => 'KataSandiLama123',
    ]);
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->fillForm([
            'name' => 'Nama Baru',
            'email' => 'nama.baru@example.test',
            'current_password' => 'KataSandiLama123',
            'password' => 'KataSandiBaru123',
            'password_confirmation' => 'KataSandiBaru123',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')
        ->and(Hash::check('KataSandiBaru123', $user->password))->toBeTrue();

    $activity = Activity::query()->where('event', 'profile_password_changed')->latest('id')->first();
    expect($activity)->not->toBeNull()
        ->and(json_encode($activity?->properties))->not->toContain('KataSandiBaru123');
});

it('keeps password resets out of audit properties and stores only a hash', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $target = User::factory()->create();
    $plainPassword = 'KataSandiBaru123';
    $this->actingAs($admin);

    app(UserAdministrationService::class)->resetPassword($admin, $target, $plainPassword);

    $target->refresh();
    expect(Hash::check($plainPassword, $target->password))->toBeTrue();

    $activity = Activity::query()->where('event', 'password_reset')->latest('id')->first();
    expect($activity)->not->toBeNull()
        ->and(json_encode($activity?->properties))->not->toContain($plainPassword);
});
