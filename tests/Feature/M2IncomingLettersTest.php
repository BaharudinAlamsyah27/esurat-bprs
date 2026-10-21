<?php

use App\Actions\RegisterIncomingLetter;
use App\Enums\UserLevel;
use App\Filament\Resources\IncomingLetterResource\Pages\CreateIncomingLetter;
use App\Models\Attachment;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Policies\IncomingLetterPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

function incomingLetterData(Classification $classification): array
{
    return [
        'received_at' => '2026-10-21',
        'letter_date' => '2026-10-20',
        'letter_number' => '001/BPRS/2026',
        'sender_name' => 'BPRS Sragen',
        'subject' => 'Pemberitahuan rapat',
        'note' => 'Mohon ditindaklanjuti.',
        'classification_id' => $classification->getKey(),
    ];
}

it('registers a letter with a yearly agenda number and optional note', function (): void {
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $action = app(RegisterIncomingLetter::class);

    $first = $action->execute($user, incomingLetterData($classification));
    $second = $action->execute($user, incomingLetterData($classification));

    expect($first->agenda_number)->toBe('0001/'.now()->year)
        ->and($second->agenda_number)->toBe('0002/'.now()->year)
        ->and($first->note)->toBe('Mohon ditindaklanjuti.')
        ->and(IncomingLetter::query()->where('registered_by', $user->getKey())->count())->toBe(2);
});

it('requires confirmation before saving a possible duplicate letter', function (): void {
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $duplicate = IncomingLetter::factory()->create([
        ...incomingLetterData($classification),
        'registered_by' => $user->getKey(),
    ]);
    $data = incomingLetterData($classification);
    $data['letter_number'] = $duplicate->letter_number;
    $data['sender_name'] = $duplicate->sender_name;
    $data['letter_date'] = $duplicate->letter_date->toDateString();

    Livewire::actingAs($user)
        ->test(CreateIncomingLetter::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors(['confirm_duplicate']);

    expect(IncomingLetter::query()->count())->toBe(1);
});

it('stores valid scans privately with their original name and sha256 checksum', function (): void {
    Storage::fake('private');
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $file = UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\ncontoh");

    $letter = app(RegisterIncomingLetter::class)->execute($user, incomingLetterData($classification), [$file]);
    $attachment = $letter->attachments()->firstOrFail();

    expect($attachment->disk)->toBe('private')
        ->and($attachment->original_name)->toBe('surat.pdf')
        ->and($attachment->sha256)->toBe(hash_file('sha256', $file->getRealPath()))
        ->and($letter->attachments_count)->toBe(1);
    Storage::disk('private')->assertExists($attachment->path);
});

it('rejects unsupported file types before creating an attachment', function (): void {
    Storage::fake('private');
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $file = UploadedFile::fake()->createWithContent('script.txt', 'not a letter');

    expect(fn () => app(RegisterIncomingLetter::class)->execute($user, incomingLetterData($classification), [$file]))
        ->toThrow(ValidationException::class);
    expect(IncomingLetter::query()->count())->toBe(0)
        ->and(Attachment::query()->count())->toBe(0);
    Storage::disk('private')->assertDirectoryEmpty('incoming-scans');
});

it('requires authentication to preview a private scan', function (): void {
    Storage::fake('private');
    $user = User::factory()->create();
    $classification = Classification::factory()->create();
    $letter = app(RegisterIncomingLetter::class)->execute(
        $user,
        incomingLetterData($classification),
        [UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\ncontoh")],
    );
    $attachment = $letter->attachments()->firstOrFail();

    $this->get(route('incoming-letters.attachments.show', $attachment))->assertRedirect();
    $this->actingAs(User::factory()->create())
        ->get(route('incoming-letters.attachments.show', $attachment))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('lets a user edit their own letter but not another user\'s letter and limits deletion to admins', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $letter = IncomingLetter::factory()->create(['registered_by' => $owner->getKey()]);
    $policy = app(IncomingLetterPolicy::class);

    expect($policy->update($owner, $letter))->toBeTrue()
        ->and($policy->update($other, $letter))->toBeFalse()
        ->and($policy->delete($owner, $letter))->toBeFalse()
        ->and($policy->delete($admin, $letter))->toBeTrue();

    $this->actingAs($owner);
    $letter->update(['note' => 'Catatan diperbarui']);
    $activity = Activity::query()->where('subject_type', IncomingLetter::class)
        ->where('subject_id', $letter->getKey())
        ->where('event', 'updated')
        ->latest('id')
        ->firstOrFail();
    expect($activity->properties->get('changed_fields'))->toContain('note')
        ->and($activity->properties->get('attributes'))->not->toHaveKey('note');

    $letter->delete();
    expect($letter->trashed())->toBeTrue()
        ->and(IncomingLetter::withTrashed()->whereKey($letter->getKey())->exists())->toBeTrue();
});
