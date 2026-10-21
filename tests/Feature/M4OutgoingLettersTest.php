<?php

use App\Actions\RegisterOutgoingLetter;
use App\Actions\UpdateOutgoingLetter;
use App\Enums\UserLevel;
use App\Filament\Resources\OutgoingLetterResource;
use App\Filament\Resources\OutgoingLetterResource\Pages\CreateOutgoingLetter;
use App\Models\Attachment;
use App\Models\Classification;
use App\Models\OutgoingLetter;
use App\Models\Setting;
use App\Models\User;
use App\Policies\OutgoingLetterPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

function outgoingLetterData(Classification $classification, array $overrides = []): array
{
    return array_merge([
        'recipient' => 'Bank Contoh',
        'subject' => 'Pemberitahuan kerja sama',
        'note' => 'Catatan internal surat.',
        'classification_id' => $classification->getKey(),
        'letter_date' => '2026-10-15',
        'sent_date' => '2026-10-16',
    ], $overrides);
}

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-21 09:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('generates unique yearly agenda and outgoing numbers with the configured format', function (): void {
    $user = User::factory()->create();
    $classification = Classification::factory()->create(['code' => '500']);
    $action = app(RegisterOutgoingLetter::class);
    $letters = [];

    for ($number = 1; $number <= 50; $number++) {
        $letters[] = $action->execute($user, outgoingLetterData($classification));
    }

    expect($letters[0]->agenda_number)->toBe('0001/2026')
        ->and($letters[49]->agenda_number)->toBe('0050/2026')
        ->and($letters[0]->letter_number)->toBe('001/500/BPRS-SRG/X/2026')
        ->and($letters[49]->letter_number)->toBe('050/500/BPRS-SRG/X/2026')
        ->and(OutgoingLetter::query()->distinct('agenda_number')->count('agenda_number'))->toBe(50)
        ->and(OutgoingLetter::query()->distinct('letter_number')->count('letter_number'))->toBe(50);
});

it('resets numbering for a new letter year and uses the letter date month', function (): void {
    $user = User::factory()->create();
    $classification = Classification::factory()->create(['code' => '500']);
    $action = app(RegisterOutgoingLetter::class);
    $lastYear = $action->execute($user, outgoingLetterData($classification, ['letter_date' => '2026-12-31']));
    $newYear = $action->execute($user, outgoingLetterData($classification, ['letter_date' => '2027-01-02']));

    expect($lastYear->agenda_number)->toBe('0001/2026')
        ->and($lastYear->letter_number)->toBe('001/500/BPRS-SRG/XII/2026')
        ->and($newYear->agenda_number)->toBe('0001/2027')
        ->and($newYear->letter_number)->toBe('001/500/BPRS-SRG/I/2027');
});

it('uses the number pattern saved in application settings', function (): void {
    $user = User::factory()->create();
    $classification = Classification::factory()->create(['code' => '500']);
    Setting::query()->create([
        'key' => 'outgoing_number_pattern',
        'value' => ['pattern' => '{seq:4}-{tahun}-{bulan_romawi}-{kode_klasifikasi}'],
    ]);

    $letter = app(RegisterOutgoingLetter::class)->execute($user, outgoingLetterData($classification));

    expect($letter->letter_number)->toBe('0001-2026-X-500');
});

it('allows only admins to override the generated letter number and prevents duplicates', function (): void {
    $user = User::factory()->create(['level' => UserLevel::User]);
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $classification = Classification::factory()->create(['code' => '500']);
    $data = outgoingLetterData($classification, ['letter_number' => 'MANUAL/001']);

    expect(fn () => app(RegisterOutgoingLetter::class)->execute($user, $data))
        ->toThrow(ValidationException::class);

    $letter = app(RegisterOutgoingLetter::class)->execute($admin, $data);
    expect($letter->letter_number)->toBe('MANUAL/001');

    expect(fn () => app(RegisterOutgoingLetter::class)->execute($admin, $data))
        ->toThrow(ValidationException::class);
});

it('skips automatically generated numbers that were already assigned manually', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $user = User::factory()->create(['level' => UserLevel::User]);
    $classification = Classification::factory()->create(['code' => '500']);
    app(RegisterOutgoingLetter::class)->execute($admin, outgoingLetterData($classification, [
        'letter_number' => '002/500/BPRS-SRG/X/2026',
    ]));

    $firstGenerated = app(RegisterOutgoingLetter::class)->execute($user, outgoingLetterData($classification));
    $secondGenerated = app(RegisterOutgoingLetter::class)->execute($user, outgoingLetterData($classification));

    expect($firstGenerated->letter_number)->toBe('001/500/BPRS-SRG/X/2026')
        ->and($secondGenerated->letter_number)->toBe('003/500/BPRS-SRG/X/2026')
        ->and($secondGenerated->agenda_number)->toBe('0003/2026');
});

it('lets a user create an outgoing letter from the form with an automatic number', function (): void {
    $user = User::factory()->create(['level' => UserLevel::User]);
    $classification = Classification::factory()->create(['code' => '500']);
    $this->actingAs($user);

    Livewire\Livewire::test(CreateOutgoingLetter::class)
        ->fillForm(outgoingLetterData($classification))
        ->call('create')
        ->assertHasNoFormErrors();

    $letter = OutgoingLetter::query()->firstOrFail();
    expect($letter->created_by)->toBe($user->getKey())
        ->and($letter->letter_number)->toBe('001/500/BPRS-SRG/X/2026')
        ->and($letter->agenda_number)->toBe('0001/2026');
});

it('stores and replaces one private final file with a checksum and authenticated access', function (): void {
    Storage::fake('private');
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $firstFile = UploadedFile::fake()->createWithContent('final.pdf', "%PDF-1.4\nSurat final");
    $letter = app(RegisterOutgoingLetter::class)->execute($user, outgoingLetterData($classification), $firstFile);
    $firstAttachment = $letter->attachments()->firstOrFail();

    expect($firstAttachment->disk)->toBe('private')
        ->and($firstAttachment->kind)->toBe('final')
        ->and($firstAttachment->sha256)->toBe(hash_file('sha256', $firstFile->getRealPath()));
    Storage::disk('private')->assertExists($firstAttachment->path);

    $this->get(route('outgoing-letters.attachments.show', $firstAttachment))->assertRedirect();
    $this->actingAs($user)
        ->get(route('outgoing-letters.attachments.show', $firstAttachment))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $secondFile = UploadedFile::fake()->createWithContent('final-baru.pdf', "%PDF-1.4\nSurat final revisi");
    app(UpdateOutgoingLetter::class)->execute($user, $letter, outgoingLetterData($classification), $secondFile);

    expect(Attachment::query()->where('attachable_type', OutgoingLetter::class)->where('attachable_id', $letter->getKey())->count())->toBe(1);
    Storage::disk('private')->assertMissing($firstAttachment->path);
    Storage::disk('private')->assertExists($letter->fresh()->attachments()->firstOrFail()->path);
    expect(Activity::query()->where('subject_type', OutgoingLetter::class)->where('subject_id', $letter->getKey())->where('event', 'file_replaced')->exists())->toBeTrue();
});

it('rejects an unsupported outgoing attachment', function (): void {
    Storage::fake('private');
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $file = UploadedFile::fake()->createWithContent('script.txt', 'not a letter');

    expect(fn () => app(RegisterOutgoingLetter::class)->execute($user, outgoingLetterData($classification), $file))
        ->toThrow(ValidationException::class);

    expect(OutgoingLetter::query()->count())->toBe(0)
        ->and(Attachment::query()->count())->toBe(0);
    Storage::disk('private')->assertDirectoryEmpty('outgoing-letters');
});

it('enforces owner edits, admin deletion, and logs only changed field names', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $classification = Classification::factory()->create();
    $letter = app(RegisterOutgoingLetter::class)->execute($owner, outgoingLetterData($classification));
    $policy = app(OutgoingLetterPolicy::class);

    expect($policy->update($owner, $letter))->toBeTrue()
        ->and($policy->update($other, $letter))->toBeFalse()
        ->and($policy->delete($owner, $letter))->toBeFalse()
        ->and($policy->delete($admin, $letter))->toBeTrue()
        ->and(OutgoingLetterResource::canViewAny())->toBeFalse();

    expect(fn () => app(UpdateOutgoingLetter::class)->execute($other, $letter, outgoingLetterData($classification)))
        ->toThrow(AuthorizationException::class);

    $this->actingAs($owner);
    $updated = app(UpdateOutgoingLetter::class)->execute($owner, $letter, outgoingLetterData($classification, ['subject' => 'Perihal diperbarui']));
    $activity = Activity::query()
        ->where('subject_type', OutgoingLetter::class)
        ->where('subject_id', $updated->getKey())
        ->where('event', 'updated')
        ->latest('id')
        ->firstOrFail();

    expect($activity->properties->get('changed_fields'))->toContain('subject')
        ->and(json_encode($activity->properties))->not->toContain('Perihal diperbarui');

    $updated->delete();
    expect($updated->trashed())->toBeTrue()
        ->and(OutgoingLetter::withTrashed()->whereKey($updated->getKey())->exists())->toBeTrue();
});
