<?php

use App\Actions\SaveDisposition;
use App\Enums\DispositionNature;
use App\Enums\UserLevel;
use App\Models\Classification;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

function dispositionData(): array
{
    return [
        'nature' => DispositionNature::Penting->value,
        'forwarded_to_input' => "Direksi\nKepala Operasional",
        'instructions' => ['segera_tindak_lanjuti', 'lainnya'],
        'instruction_other' => 'Siapkan ringkasan',
        'note' => 'Mohon diselesaikan minggu ini.',
        'disposition_date' => '2026-10-21',
    ];
}

function incomingLetterForDisposition(User $user): IncomingLetter
{
    return IncomingLetter::factory()->create([
        'registered_by' => $user->getKey(),
        'classification_id' => Classification::factory()->create()->getKey(),
        'agenda_number' => '0001/2026',
        'agenda_seq' => random_int(1, 999999),
    ]);
}

it('lets only admins create or update one disposition per incoming letter', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $letter = incomingLetterForDisposition($admin);

    $first = app(SaveDisposition::class)->execute($admin, $letter, dispositionData());
    $updatedData = dispositionData();
    $updatedData['nature'] = DispositionNature::Segera->value;
    $updatedData['forwarded_to_input'] = 'Direksi';
    $second = app(SaveDisposition::class)->execute($admin, $letter, $updatedData);

    expect($second->getKey())->toBe($first->getKey())
        ->and($second->nature)->toBe(DispositionNature::Segera)
        ->and($second->forwarded_to)->toBe(['Direksi'])
        ->and(Disposition::query()->where('incoming_letter_id', $letter->getKey())->count())->toBe(1);

    $activity = Activity::query()->where('subject_type', Disposition::class)
        ->where('subject_id', $first->getKey())
        ->where('event', 'updated')
        ->latest('id')
        ->firstOrFail();
    expect($activity->properties->get('changed_fields'))->toContain('forwarded_to')
        ->and($activity->properties->all())->not->toHaveKey('attributes');
});

it('denies users from creating or changing a disposition', function (): void {
    $user = User::factory()->create(['level' => UserLevel::User]);
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $letter = incomingLetterForDisposition($user);
    $disposition = app(SaveDisposition::class)->execute($admin, $letter, dispositionData());

    $updatedData = dispositionData();
    $updatedData['nature'] = DispositionNature::Segera->value;

    expect(fn () => app(SaveDisposition::class)->execute($user, $letter, $updatedData))
        ->toThrow(AuthorizationException::class);
    expect(Disposition::query()->count())->toBe(1)
        ->and($disposition->fresh()->nature)->toBe(DispositionNature::Penting);
});

it('prints a one-page PDF for signed-in users and increments the print count', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $user = User::factory()->create(['level' => UserLevel::User]);
    Institution::query()->create([
        'name' => 'BPRS Sragen',
        'city' => 'Sragen',
        'leader_name' => 'Pimpinan Contoh',
        'leader_position' => 'Direktur Utama',
    ]);
    $letter = incomingLetterForDisposition($admin);
    $disposition = app(SaveDisposition::class)->execute($admin, $letter, dispositionData());

    $this->get(route('dispositions.print', $disposition))->assertRedirect();

    $response = $this->actingAs($user)->get(route('dispositions.print', $disposition));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    preg_match_all('/\/Type\s*\/Page\b/', $response->getContent(), $pageObjects);
    expect($disposition->fresh()->print_count)->toBe(1)
        ->and(count($pageObjects[0]))->toBe(1)
        ->and(Activity::query()->where('subject_type', Disposition::class)->where('event', 'printed')->count())->toBe(1);

    $response = $this->actingAs($user)->get(route('dispositions.print', $disposition));
    $response->assertOk();
    expect($disposition->fresh()->print_count)->toBe(2);
});

it('renders the required letter fields and institution signature in the PDF view', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $letter = incomingLetterForDisposition($admin);
    $disposition = app(SaveDisposition::class)->execute($admin, $letter, dispositionData());
    $institution = Institution::query()->create([
        'name' => 'BPRS Sragen',
        'city' => 'Sragen',
        'leader_name' => 'Pimpinan Contoh',
        'leader_position' => 'Direktur Utama',
    ]);

    $html = view('pdf.disposition', [
        'disposition' => $disposition,
        'letter' => $letter->load('classification'),
        'institution' => $institution,
        'logoData' => null,
        'instructionLabels' => $disposition->instructionLabels(),
        'dispositionDate' => '21 Oktober 2026',
    ])->render();

    expect($html)->toContain('LEMBAR DISPOSISI')
        ->and($html)->toContain($letter->sender_name)
        ->and($html)->toContain($letter->agenda_number)
        ->and($html)->toContain($letter->subject)
        ->and($html)->toContain('Pimpinan Contoh')
        ->and($html)->toContain('Segera tindak lanjuti')
        ->and($html)->toContain('Siapkan ringkasan');
});
