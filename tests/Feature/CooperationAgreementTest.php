<?php

use App\Actions\SaveCooperationAgreement;
use App\Enums\UserLevel;
use App\Filament\Resources\CooperationAgreementResource;
use App\Models\CooperationAgreement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

it('creates a cooperation agreement with private pks and legality files', function (): void {
    Storage::fake('private');

    $user = User::factory()->create(['level' => UserLevel::User]);
    $pksFile = UploadedFile::fake()->create('pks-mitra.pdf', 120, 'application/pdf');
    $legalityFile = UploadedFile::fake()->image('ktp-mitra.jpg');

    $agreement = app(SaveCooperationAgreement::class)->execute(
        user: $user,
        data: [
            'bss_pks_number' => '001/PKS/BPRS-SRG/XI/2026',
            'partner_pks_number' => '019/MITRA/XI/2026',
            'partner_npwp' => '01.234.567.8-901.000',
            'agreement_date' => '2026-11-16',
            'partner_name' => 'PT Mitra Sejahtera',
            'subject' => 'Kerjasama Payroll Karyawan',
            'pic_name' => 'Budi Santoso',
        ],
        pksFile: $pksFile,
        legalityFile: $legalityFile,
    );

    expect($agreement->bss_pks_number)->toBe('001/PKS/BPRS-SRG/XI/2026')
        ->and($agreement->partner_name)->toBe('PT Mitra Sejahtera')
        ->and($agreement->pksAttachment())->not->toBeNull()
        ->and($agreement->legalityAttachment())->not->toBeNull();

    Storage::disk('private')->assertExists($agreement->pksAttachment()->path);
    Storage::disk('private')->assertExists($agreement->legalityAttachment()->path);

    expect(Activity::query()->where('log_name', 'cooperation_agreement')->where('event', 'created')->exists())->toBeTrue();
});

it('rejects unsupported file types for pks or partner legality', function (): void {
    Storage::fake('private');
    $user = User::factory()->create(['level' => UserLevel::User]);
    $invalid = UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload');

    expect(fn () => app(SaveCooperationAgreement::class)->execute(
        user: $user,
        data: [
            'bss_pks_number' => '002/PKS/BPRS-SRG/XI/2026',
            'agreement_date' => '2026-11-16',
            'partner_name' => 'CV Amanah',
            'subject' => 'Kerjasama IT',
            'pic_name' => 'Ani',
        ],
        legalityFile: $invalid,
    ))->toThrow(ValidationException::class);

    expect(CooperationAgreement::query()->count())->toBe(0);
});

it('serves cooperation agreement attachments only to authenticated users and logs download', function (): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $pksFile = UploadedFile::fake()->create('dokumen-pks.pdf', 100, 'application/pdf');

    $agreement = app(SaveCooperationAgreement::class)->execute(
        user: $user,
        data: [
            'bss_pks_number' => '003/PKS/BPRS-SRG/XI/2026',
            'agreement_date' => '2026-11-16',
            'partner_name' => 'Koperasi Maju',
            'subject' => 'Kerjasama Pembiayaan',
            'pic_name' => 'Sari',
        ],
        pksFile: $pksFile,
    );

    $attachment = $agreement->pksAttachment();
    $url = route('cooperation-agreements.attachments.show', $attachment);

    $this->get($url)->assertRedirect();

    $this->actingAs($user)
        ->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(Activity::query()->where('log_name', 'cooperation_agreement')->where('event', 'download_attachment')->exists())->toBeTrue();
});

it('enforces owner edit and admin-only soft delete on cooperation agreements', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $owner = User::factory()->create(['level' => UserLevel::User]);
    $other = User::factory()->create(['level' => UserLevel::User]);

    $agreement = CooperationAgreement::factory()->create(['created_by' => $owner->getKey()]);

    expect($owner->can('update', $agreement))->toBeTrue()
        ->and($other->can('update', $agreement))->toBeFalse()
        ->and($owner->can('delete', $agreement))->toBeFalse()
        ->and($admin->can('delete', $agreement))->toBeTrue();

    $agreement->delete();
    expect($agreement->fresh()->trashed())->toBeTrue();
});

it('renders the cooperation agreement page under SURAT navigation for signed-in users', function (): void {
    $user = User::factory()->create(['level' => UserLevel::User]);

    $this->actingAs($user)
        ->get(CooperationAgreementResource::getUrl('index'))
        ->assertOk()
        ->assertSee(__('cooperation.navigation'));
});
