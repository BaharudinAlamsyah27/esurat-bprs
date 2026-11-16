<?php

use App\Actions\SaveCooperationAgreement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('lists, searches, and serves cooperation agreement details and files via api', function (): void {
    Storage::fake('private');

    $user = User::factory()->create(['name' => 'Pegawai Pembiayaan']);
    $pksFile = UploadedFile::fake()->create('pks.pdf', 80, 'application/pdf');
    $legalityFile = UploadedFile::fake()->image('ktp.jpg');

    $agreement = app(SaveCooperationAgreement::class)->execute(
        user: $user,
        data: [
            'bss_pks_number' => '010/PKS/BPRS-SRG/XI/2026',
            'partner_pks_number' => '088/MITRA/2026',
            'partner_npwp' => '01.234.567.8-901.000',
            'agreement_date' => '2026-11-16',
            'partner_name' => 'PT Nusantara Abadi',
            'subject' => 'Kerjasama Payroll',
            'pic_name' => 'Rudi Hartono',
        ],
        pksFile: $pksFile,
        legalityFile: $legalityFile,
    );

    Sanctum::actingAs($user);

    $this->getJson('/api/cooperation-agreements?search=Nusantara')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.bss_pks_number', '010/PKS/BPRS-SRG/XI/2026')
        ->assertJsonPath('data.0.pic_name', 'Rudi Hartono');

    $detail = $this->getJson("/api/cooperation-agreements/{$agreement->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.partner_name', 'PT Nusantara Abadi');

    $pksUrl = $detail->json('data.pks_file_url');
    $legalityUrl = $detail->json('data.partner_legality_url');

    expect($pksUrl)->not->toBeNull()
        ->and($legalityUrl)->not->toBeNull();

    $this->get($pksUrl)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get($legalityUrl)->assertOk();
});
