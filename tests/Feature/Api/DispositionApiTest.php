<?php

use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Enums\UserLevel;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Notifications\DispositionCompleted;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

function apiDispositionFor(User $recipient): DispositionRecipient
{
    $creator = User::factory()->create(['name' => 'Direktur Utama']);

    $disposition = Disposition::factory()->create([
        'nature' => DispositionNature::Penting->value,
        'note' => 'Mohon ditindaklanjuti minggu ini.',
        'created_by' => $creator->getKey(),
        'updated_by' => $creator->getKey(),
    ]);

    return DispositionRecipient::factory()->create([
        'disposition_id' => $disposition->getKey(),
        'user_id' => $recipient->getKey(),
    ]);
}

it('lists only the dispositions addressed to the signed-in user', function (): void {
    $me = User::factory()->create(['name' => 'Staf Operasional']);
    $other = User::factory()->create();

    apiDispositionFor($me);
    apiDispositionFor($other);

    Sanctum::actingAs($me);

    $response = $this->getJson('/api/dispositions');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.disposition.sender', 'Direktur Utama')
        ->assertJsonPath('data.0.disposition.nature', DispositionNature::Penting->value)
        ->assertJsonPath('data.0.disposition.instructions', [__('disposition.instructions.untuk_diketahui')]);
});

it('marks the disposition as read when the recipient opens it', function (): void {
    $me = User::factory()->create();
    $recipient = apiDispositionFor($me);

    Sanctum::actingAs($me);

    $this->getJson("/api/dispositions/{$recipient->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.status', 'read');

    expect($recipient->fresh()->read_at)->not->toBeNull();
});

it('blocks a user from opening a disposition addressed to someone else', function (): void {
    $me = User::factory()->create();
    $recipient = apiDispositionFor(User::factory()->create());

    Sanctum::actingAs($me);

    $this->getJson("/api/dispositions/{$recipient->getKey()}")->assertForbidden();

    expect($recipient->fresh()->read_at)->toBeNull();
});

it('lets the recipient mark a disposition as done with a note, logs audit, and notifies creator', function (): void {
    Notification::fake();

    $me = User::factory()->create();
    $recipient = apiDispositionFor($me);
    $creator = $recipient->disposition->creator;

    Sanctum::actingAs($me);

    $this->postJson("/api/dispositions/{$recipient->getKey()}/done", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('note');

    $this->postJson("/api/dispositions/{$recipient->getKey()}/done", [
        'note' => str_repeat('a', 256),
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('note');

    $this->postJson("/api/dispositions/{$recipient->getKey()}/done", [
        'note' => 'Sudah dikoordinasikan dengan bagian pembiayaan.',
    ])->assertOk()->assertJsonPath('data.status', 'done');

    expect($recipient->fresh()->done_note)->toBe('Sudah dikoordinasikan dengan bagian pembiayaan.');
    Notification::assertSentTo($creator, DispositionCompleted::class);
    expect(Activity::query()->where('log_name', 'disposition')->where('event', 'completed')->exists())->toBeTrue();
});

it('serves letter files through the api using a token instead of a session', function (): void {
    Storage::fake('private');

    $me = User::factory()->create();
    $letter = IncomingLetter::factory()->create(['registered_by' => $me->getKey()]);

    Storage::disk('private')->put('scans/contoh.pdf', '%PDF-1.4 contoh');

    $attachment = $letter->attachments()->create([
        'disk' => 'private',
        'path' => 'scans/contoh.pdf',
        'original_name' => 'contoh.pdf',
        'mime' => 'application/pdf',
        'size' => 15,
        'sha256' => hash('sha256', '%PDF-1.4 contoh'),
        'kind' => 'scan',
        'uploaded_by' => $me->getKey(),
    ]);

    $this->getJson(route('api.attachments.show', $attachment))->assertUnauthorized();

    Sanctum::actingAs($me);

    $this->get(route('api.attachments.show', $attachment))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('filters letters by the real recipient column', function (): void {
    $me = User::factory()->create(['level' => 'admin']);
    Sanctum::actingAs($me);

    OutgoingLetter::factory()->create(['recipient' => 'PT Mitra Sejahtera', 'created_by' => $me->getKey()]);
    OutgoingLetter::factory()->create(['recipient' => 'Kantor Pusat']);

    $this->getJson('/api/outgoing-letters?search=Mitra')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.recipient', 'PT Mitra Sejahtera');
});

it('returns exact dashboard summary counts for the mobile home screen', function (): void {
    $me = User::factory()->create();
    apiDispositionFor($me);

    Sanctum::actingAs($me);

    $this->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.unread_dispositions', 1)
        ->assertJsonPath('data.dispositions_total', 1)
        ->assertJsonPath('data.incoming_total', 1);
});

it('lets admins create a disposition for an incoming letter via api and blocks regular users', function (): void {
    Notification::fake();

    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $user = User::factory()->create(['level' => UserLevel::User]);
    $letter = IncomingLetter::factory()->create(['registered_by' => $admin->getKey()]);

    Sanctum::actingAs($user);
    $this->getJson('/api/disposition-options')->assertForbidden();
    $this->postJson("/api/incoming-letters/{$letter->getKey()}/disposition", [
        'nature' => DispositionNature::Penting->value,
        'recipient_user_ids' => [$user->getKey()],
        'instructions' => [DispositionInstruction::SegeraTindakLanjuti->value],
        'disposition_date' => '2026-11-16',
    ])->assertForbidden();

    Sanctum::actingAs($admin);
    $this->getJson('/api/disposition-options')
        ->assertOk()
        ->assertJsonStructure(['data' => ['natures', 'instructions', 'recipients']]);

    $this->postJson("/api/incoming-letters/{$letter->getKey()}/disposition", [
        'nature' => DispositionNature::Penting->value,
        'recipient_user_ids' => [$user->getKey()],
        'instructions' => [DispositionInstruction::SegeraTindakLanjuti->value],
        'note' => 'Proses segera dari HP',
        'disposition_date' => '2026-11-16',
    ])->assertOk()->assertJsonPath('data.recipient_count', 1);

    expect($letter->fresh()->disposition)->not->toBeNull();
});
