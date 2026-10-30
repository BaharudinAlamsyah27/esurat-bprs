<?php

namespace Tests\Feature;

use App\Actions\CompleteDispositionRecipient;
use App\Actions\MarkDispositionRead;
use App\Actions\RegisterDeviceToken;
use App\Actions\SaveDisposition;
use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Enums\DispositionRecipientStatus;
use App\Enums\UserLevel;
use App\Models\Classification;
use App\Models\DeviceToken;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Notifications\DispositionAssigned;
use App\Notifications\DispositionCompleted;
use App\Services\Push\FcmClient;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('admin can assign digital disposition recipients and recipient receives notification', function () {
    Notification::fake();

    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $recipient1 = User::factory()->create(['level' => UserLevel::User, 'name' => 'Budi Santoso', 'position_title' => 'Staf Operasional']);
    $recipient2 = User::factory()->create(['level' => UserLevel::User, 'name' => 'Siti Rahma', 'position_title' => 'Kasir']);

    $classification = Classification::factory()->create(['code' => '500', 'name' => 'Keuangan']);
    $letter = IncomingLetter::factory()->create([
        'agenda_year' => 2026,
        'agenda_seq' => 1,
        'agenda_number' => '0001/2026',
        'letter_number' => 'SK/01/X/2026',
        'sender_name' => 'OJK Solo',
        'subject' => 'Surat Edaran Pengawasan',
        'classification_id' => $classification->id,
        'letter_date' => '2026-10-27',
        'received_at' => '2026-10-27',
        'registered_by' => $admin->id,
    ]);

    $disposition = app(SaveDisposition::class)->execute($admin, $letter, [
        'nature' => DispositionNature::Penting->value,
        'recipient_user_ids' => [$recipient1->id, $recipient2->id],
        'other_recipients' => 'Pihak Ketiga',
        'instructions' => [DispositionInstruction::SegeraTindakLanjuti->value],
        'disposition_date' => '2026-10-27',
        'note' => 'Mohon dipelajari dan disiapkan konsep tindak lanjutnya.',
    ]);

    expect($disposition)->toBeInstanceOf(Disposition::class);
    expect($disposition->recipients)->toHaveCount(2);

    $rec1 = $disposition->recipients()->where('user_id', $recipient1->id)->first();
    expect($rec1)->not->toBeNull();
    expect($rec1->status)->toBe(DispositionRecipientStatus::New);

    Notification::assertSentTo($recipient1, DispositionAssigned::class);
    Notification::assertSentTo($recipient2, DispositionAssigned::class);

    // Verify forwarded_to has names for backward compatibility with PDF print
    expect($disposition->forwarded_to)->toContain('Budi Santoso (Staf Operasional)');
    expect($disposition->forwarded_to)->toContain('Siti Rahma (Kasir)');
    expect($disposition->forwarded_to)->toContain('Pihak Ketiga');
});

test('recipient opening disposition marks it as read', function () {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $pegawai = User::factory()->create(['level' => UserLevel::User]);

    $letter = IncomingLetter::factory()->create([
        'registered_by' => $admin->id,
    ]);

    $disposition = app(SaveDisposition::class)->execute($admin, $letter, [
        'nature' => DispositionNature::Biasa->value,
        'recipient_user_ids' => [$pegawai->id],
        'instructions' => [DispositionInstruction::UntukDiketahui->value],
        'disposition_date' => '2026-10-27',
    ]);

    $rec = $disposition->recipients()->where('user_id', $pegawai->id)->first();
    expect($rec->status)->toBe(DispositionRecipientStatus::New);

    // Pegawai opens/reads disposition
    app(MarkDispositionRead::class)->execute($pegawai, $rec);

    $rec->refresh();
    expect($rec->status)->toBe(DispositionRecipientStatus::Read);
    expect($rec->read_at)->not->toBeNull();
});

test('recipient can mark disposition as done with note and creator is notified', function () {
    Notification::fake();

    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $pegawai = User::factory()->create(['level' => UserLevel::User]);

    $letter = IncomingLetter::factory()->create([
        'registered_by' => $admin->id,
    ]);

    $disposition = app(SaveDisposition::class)->execute($admin, $letter, [
        'nature' => DispositionNature::Segera->value,
        'recipient_user_ids' => [$pegawai->id],
        'instructions' => [DispositionInstruction::Koordinasikan->value],
        'disposition_date' => '2026-10-27',
    ]);

    $rec = $disposition->recipients()->where('user_id', $pegawai->id)->first();

    // Pegawai marks as done
    app(CompleteDispositionRecipient::class)->execute($pegawai, $rec, 'Sudah dihadiri via Zoom');

    $rec->refresh();
    expect($rec->status)->toBe(DispositionRecipientStatus::Done);
    expect($rec->done_at)->not->toBeNull();
    expect($rec->done_note)->toBe('Sudah dihadiri via Zoom');

    Notification::assertSentTo($admin, DispositionCompleted::class);
});

test('device token endpoint registers and revokes tokens', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('device-tokens.store'), [
        'token' => 'fcm-dummy-token-12345',
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_name' => 'Samsung A52',
    ]);

    $response->assertOk();
    expect(DeviceToken::query()->where('token', 'fcm-dummy-token-12345')->where('user_id', $user->id)->exists())->toBeTrue();

    // Revoke token
    $deleteResponse = $this->actingAs($user)->deleteJson(route('device-tokens.destroy'), [
        'token' => 'fcm-dummy-token-12345',
    ]);

    $deleteResponse->assertOk();
    expect(DeviceToken::query()->where('token', 'fcm-dummy-token-12345')->exists())->toBeFalse();
});

test('user logout removes session device tokens', function () {
    $user = User::factory()->create();
    session()->start();
    $sessionId = session()->getId();

    app(RegisterDeviceToken::class)->execute($user, [
        'token' => 'token-session-bound',
        'platform' => 'android',
        'session_id' => $sessionId,
    ]);

    expect(DeviceToken::query()->where('token', 'token-session-bound')->exists())->toBeTrue();

    // Fire logout
    event(new Logout('web', $user));

    expect(DeviceToken::query()->where('token', 'token-session-bound')->exists())->toBeFalse();
});

test('fcm client safely skips push when disabled or credentials missing', function () {
    $user = User::factory()->create();
    $device = DeviceToken::create([
        'user_id' => $user->id,
        'token' => 'token-abc',
        'platform' => 'android',
    ]);

    $client = new FcmClient;
    $result = $client->send($device, [
        'title' => 'E-Surat BPRS',
        'body' => 'Anda menerima disposisi baru',
    ], ['type' => 'disposition']);

    expect($result)->toBeTrue();
});
