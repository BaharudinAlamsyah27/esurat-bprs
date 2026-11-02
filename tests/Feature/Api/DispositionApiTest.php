<?php

use App\Enums\DispositionNature;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

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

it('lets the recipient mark a disposition as done with a note', function (): void {
    $me = User::factory()->create();
    $recipient = apiDispositionFor($me);

    Sanctum::actingAs($me);

    $this->postJson("/api/dispositions/{$recipient->getKey()}/done", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('note');

    $this->postJson("/api/dispositions/{$recipient->getKey()}/done", [
        'note' => 'Sudah dikoordinasikan dengan bagian pembiayaan.',
    ])->assertOk()->assertJsonPath('data.status', 'done');

    expect($recipient->fresh()->done_note)->toBe('Sudah dikoordinasikan dengan bagian pembiayaan.');
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
