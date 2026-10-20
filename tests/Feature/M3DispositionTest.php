<?php

use App\Actions\CloseDispositionManually;
use App\Actions\CreateDisposition;
use App\Actions\UpdateRecipientStatus;
use App\Enums\DispositionRecipientStatus;
use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Filament\Resources\IncomingLetterResource;
use App\Models\Disposition;
use App\Models\DispositionInstruction;
use App\Models\IncomingLetter;
use App\Models\Unit;
use App\Models\User;
use App\Services\DispositionNotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function prepareForwardedLetter(User $recipient): IncomingLetter
{
    return IncomingLetter::factory()->create([
        'status' => IncomingLetterStatus::Forwarded,
        'initial_recipient_id' => $recipient->id,
        'registered_by' => User::factory(),
    ]);
}

it('creates a disposition and completes the incoming letter after its recipient finishes', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);

    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();

    expect($letter->refresh()->status)->toBe(IncomingLetterStatus::InDisposition)
        ->and($disposition->refresh()->status)->toBe(DispositionStatus::Pending)
        ->and($recipientRecord->status)->toBe(DispositionRecipientStatus::Pending);

    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::Read);
    expect($recipientRecord->refresh()->status)->toBe(DispositionRecipientStatus::Read)
        ->and($recipientRecord->read_at)->not->toBeNull()
        ->and($disposition->refresh()->status)->toBe(DispositionStatus::Pending)
        ->and($letter->refresh()->status)->toBe(IncomingLetterStatus::InDisposition);

    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::InProgress);
    expect($recipientRecord->refresh()->status)->toBe(DispositionRecipientStatus::InProgress)
        ->and($recipientRecord->started_at)->not->toBeNull()
        ->and($disposition->refresh()->status)->toBe(DispositionStatus::InProgress)
        ->and($letter->refresh()->status)->toBe(IncomingLetterStatus::InProgress);

    expect(fn () => app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::Done))
        ->toThrow(ValidationException::class);

    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::Done, 'Sudah ditindaklanjuti.');

    expect($letter->refresh()->status)->toBe(IncomingLetterStatus::Completed)
        ->and($disposition->refresh()->status)->toBe(DispositionStatus::Completed)
        ->and($recipientRecord->refresh()->done_at)->not->toBeNull();
});

it('sends database notifications to assigned users and active members of assigned units', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $directRecipient = User::factory()->create();
    $directRecipient->assignRole('staf');
    $unit = Unit::factory()->create();
    $unitMember = User::factory()->create(['unit_id' => $unit->id]);
    $unitMember->assignRole('staf');
    $inactiveMember = User::factory()->create(['unit_id' => $unit->id, 'is_active' => false]);
    $inactiveMember->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, [
        'user_ids' => [$directRecipient->id],
        'unit_ids' => [$unit->id],
    ]);

    app(DispositionNotificationService::class)->notifyCreated($disposition);

    $notification = $directRecipient->notifications()->firstOrFail();
    expect($directRecipient->notifications()->count())->toBe(1)
        ->and($unitMember->notifications()->count())->toBe(1)
        ->and($inactiveMember->notifications()->count())->toBe(0)
        ->and($director->notifications()->count())->toBe(0)
        ->and($notification->data['format'])->toBe('filament')
        ->and($notification->data['title'])->toBe(__('dispositions.received'));
});

it('does not complete the letter until every active recipient is done', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $firstRecipient = User::factory()->create();
    $firstRecipient->assignRole('staf');
    $secondRecipient = User::factory()->create();
    $secondRecipient->assignRole('kepala_unit');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$firstRecipient->id, $secondRecipient->id]]);

    foreach ([$firstRecipient, $secondRecipient] as $index => $recipient) {
        $recipientRecord = $disposition->recipients()->where('recipient_user_id', $recipient->id)->firstOrFail();
        app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::Read);
        app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::InProgress);
        app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::Done, 'Tugas selesai.');

        expect($letter->refresh()->status)->toBe($index === 0 ? IncomingLetterStatus::InProgress : IncomingLetterStatus::Completed);
    }
});

it('rejects skipping or repeating a recipient status transition', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();

    expect(fn () => app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::InProgress))
        ->toThrow(ValidationException::class);

    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::Read);

    expect(fn () => app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::Read))
        ->toThrow(ValidationException::class);
});

it('automatically marks a recipient as read when opening the incoming letter', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();

    $this->actingAs($recipient)
        ->get(IncomingLetterResource::getUrl('view', ['record' => $letter]))
        ->assertOk();

    expect($recipientRecord->refresh()->status)->toBe(DispositionRecipientStatus::Read)
        ->and($recipientRecord->read_at)->not->toBeNull();
});

it('rejects a user who is not an assigned disposition recipient', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $outsider = User::factory()->create();
    $outsider->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);

    expect(fn () => app(UpdateRecipientStatus::class)->execute(
        $outsider,
        $disposition->recipients()->firstOrFail(),
        DispositionRecipientStatus::Read,
    ))->toThrow(AuthorizationException::class);
});

it('rejects creating a disposition without the required permission', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staf');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($staff);

    expect(fn () => app(CreateDisposition::class)->execute($staff, $letter, ['user_ids' => [$recipient->id]]))
        ->toThrow(AuthorizationException::class);
    expect(Disposition::query()->count())->toBe(0);
});

it('allows an active member of an assigned unit to act and rejects a user outside that unit', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $unit = Unit::factory()->create();
    $member = User::factory()->create(['unit_id' => $unit->id]);
    $member->assignRole('staf');
    $outsider = User::factory()->create();
    $outsider->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['unit_ids' => [$unit->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();

    app(UpdateRecipientStatus::class)->execute($member, $recipientRecord, DispositionRecipientStatus::Read);

    expect($recipientRecord->refresh()->status)->toBe(DispositionRecipientStatus::Read);
    expect(fn () => app(UpdateRecipientStatus::class)->execute($outsider, $recipientRecord, DispositionRecipientStatus::InProgress))
        ->toThrow(AuthorizationException::class);
});

it('requires a reason and closes all recipients when a disposition is closed manually', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $otherDirector = User::factory()->create();
    $otherDirector->assignRole('direksi');

    expect(fn () => app(CloseDispositionManually::class)->execute($otherDirector, $disposition, 'Alasan mencukupi.'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(CloseDispositionManually::class)->execute($director, $disposition, '   '))
        ->toThrow(ValidationException::class);
    expect(fn () => app(CloseDispositionManually::class)->execute($director, $disposition, 'abc'))
        ->toThrow(ValidationException::class);

    app(CloseDispositionManually::class)->execute($director, $disposition, 'Tidak perlu ditindaklanjuti.');

    expect($letter->refresh()->status)->toBe(IncomingLetterStatus::Completed)
        ->and($letter->completion_note)->toBe('Tidak perlu ditindaklanjuti.')
        ->and($disposition->recipients()->firstOrFail()->status)->toBe(DispositionRecipientStatus::Cancelled);
});

it('stores a report attachment privately with its SHA-256 hash', function (): void {
    Storage::fake('private');
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();
    $file = UploadedFile::fake()->create('laporan.pdf', 12, 'application/pdf');

    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::Read);
    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::InProgress);
    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::Done, 'Laporan selesai.', $file);

    $attachment = $disposition->attachments()->firstOrFail();
    expect($attachment->disk)->toBe('private')
        ->and($attachment->sha256)->toBe(hash_file('sha256', Storage::disk('private')->path($attachment->path)))
        ->and(Storage::disk('private')->exists($attachment->path))->toBeTrue();
});

it('rejects inactive disposition recipients', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $inactive = User::factory()->create(['is_active' => false]);
    $letter = prepareForwardedLetter($director);

    expect(fn () => app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$inactive->id]]))
        ->toThrow(ValidationException::class);
    expect(Disposition::query()->count())->toBe(0);
});

it('rejects users whose role cannot receive dispositions', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $auditor = User::factory()->create();
    $auditor->assignRole('auditor');
    $letter = prepareForwardedLetter($director);

    expect(fn () => app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$auditor->id]]))
        ->toThrow(ValidationException::class);
    expect(Disposition::query()->count())->toBe(0);
});

it('validates every disposition form input on the server', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $inactiveUnit = Unit::factory()->create(['is_active' => false]);
    $inactiveInstruction = DispositionInstruction::factory()->create(['is_active' => false]);
    $letter = prepareForwardedLetter($director);

    $invalidInputs = [
        ['user_ids' => 'bukan-daftar'],
        ['user_ids' => [$recipient->id], 'notes' => str_repeat('a', 3001)],
        ['user_ids' => [$recipient->id], 'urgency' => 'tidak-valid'],
        ['user_ids' => [$recipient->id], 'due_at' => 'bukan-tanggal'],
        ['unit_ids' => [$inactiveUnit->id]],
        ['user_ids' => [$recipient->id], 'instruction_ids' => [$inactiveInstruction->id]],
        [],
    ];

    foreach ($invalidInputs as $input) {
        expect(fn () => app(CreateDisposition::class)->execute($director, $letter, $input))
            ->toThrow(ValidationException::class);
    }

    expect(Disposition::query()->count())->toBe(0);
});

it('saves valid recipients, instructions, notes, urgency, and due date together', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $unit = Unit::factory()->create();
    $instruction = DispositionInstruction::factory()->create();
    $letter = prepareForwardedLetter($director);
    $dueAt = now()->addDays(2)->startOfMinute();

    $disposition = app(CreateDisposition::class)->execute($director, $letter, [
        'user_ids' => [$recipient->id],
        'unit_ids' => [$unit->id],
        'instruction_ids' => [$instruction->id],
        'notes' => 'Mohon ditindaklanjuti.',
        'urgency' => 'segera',
        'due_at' => $dueAt->toDateTimeString(),
    ]);

    expect($disposition->notes)->toBe('Mohon ditindaklanjuti.')
        ->and($disposition->urgency)->toBe('segera')
        ->and($disposition->due_at->equalTo($dueAt))->toBeTrue()
        ->and($disposition->recipients)->toHaveCount(2)
        ->and($disposition->instructions->modelKeys())->toContain($instruction->id);
});

it('uses the only active parent when a recipient leaves the parent selection blank', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $head = User::factory()->create();
    $head->assignRole('kepala_unit');
    $staff = User::factory()->create();
    $staff->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $first = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$head->id]]);

    $followUp = app(CreateDisposition::class)->execute($head, $letter->refresh(), ['user_ids' => [$staff->id]]);

    expect($followUp->parent_id)->toBe($first->id);
});

it('asks for a parent when a recipient has more than one active disposition', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $head = User::factory()->create();
    $head->assignRole('kepala_unit');
    $staff = User::factory()->create();
    $staff->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$head->id]]);
    app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$head->id]]);

    expect(fn () => app(CreateDisposition::class)->execute($head, $letter->refresh(), ['user_ids' => [$staff->id]]))
        ->toThrow(ValidationException::class);
});

it('rejects a parent disposition from a different incoming letter', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $head = User::factory()->create();
    $head->assignRole('kepala_unit');
    $staff = User::factory()->create();
    $staff->assignRole('staf');
    $firstLetter = prepareForwardedLetter($director);
    $otherLetter = prepareForwardedLetter($director);
    $parent = app(CreateDisposition::class)->execute($director, $firstLetter, ['user_ids' => [$head->id]]);

    expect(fn () => app(CreateDisposition::class)->execute(
        $head,
        $otherLetter,
        ['user_ids' => [$staff->id]],
        $parent,
    ))->toThrow(AuthorizationException::class);
    expect($otherLetter->dispositions()->count())->toBe(0);
});

it('rejects a disposition when the incoming letter is already complete', function (): void {
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $letter->forceFill(['status' => IncomingLetterStatus::Completed])->save();

    expect(fn () => app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]))
        ->toThrow(AuthorizationException::class);
    expect(Disposition::query()->count())->toBe(0);
});

it('rejects an invalid report attachment before changing the recipient status', function (): void {
    Storage::fake('private');
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();
    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::Read);
    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::InProgress);
    $invalidFile = UploadedFile::fake()->create('laporan.txt', 20, 'text/plain');

    expect(fn () => app(UpdateRecipientStatus::class)->execute(
        $recipient,
        $recipientRecord->refresh(),
        DispositionRecipientStatus::Done,
        'Laporan selesai.',
        $invalidFile,
    ))->toThrow(ValidationException::class);

    expect($recipientRecord->refresh()->status)->toBe(DispositionRecipientStatus::InProgress)
        ->and($disposition->attachments()->count())->toBe(0);
});

it('rejects oversized reports and report notes longer than the form limit', function (): void {
    Storage::fake('private');
    $director = User::factory()->create();
    $director->assignRole('direksi');
    $recipient = User::factory()->create();
    $recipient->assignRole('staf');
    $letter = prepareForwardedLetter($director);
    $disposition = app(CreateDisposition::class)->execute($director, $letter, ['user_ids' => [$recipient->id]]);
    $recipientRecord = $disposition->recipients()->firstOrFail();
    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord, DispositionRecipientStatus::Read);
    app(UpdateRecipientStatus::class)->execute($recipient, $recipientRecord->refresh(), DispositionRecipientStatus::InProgress);

    expect(fn () => app(UpdateRecipientStatus::class)->execute(
        $recipient,
        $recipientRecord->refresh(),
        DispositionRecipientStatus::Done,
        str_repeat('a', 3001),
    ))->toThrow(ValidationException::class);

    $oversizedFile = UploadedFile::fake()->create('laporan.pdf', 10241, 'application/pdf');
    expect(fn () => app(UpdateRecipientStatus::class)->execute(
        $recipient,
        $recipientRecord->refresh(),
        DispositionRecipientStatus::Done,
        'Laporan selesai.',
        $oversizedFile,
    ))->toThrow(ValidationException::class);

    expect($recipientRecord->refresh()->status)->toBe(DispositionRecipientStatus::InProgress)
        ->and($disposition->attachments()->count())->toBe(0);
});
