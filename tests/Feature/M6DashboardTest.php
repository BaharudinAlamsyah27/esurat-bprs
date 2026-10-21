<?php

use App\Enums\DispositionNature;
use App\Enums\UserLevel;
use App\Filament\Widgets\LettersByClassificationChart;
use App\Filament\Widgets\LettersByMonthChart;
use App\Filament\Widgets\SummaryCardsWidget;
use App\Models\Classification;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function (): void {
    Cache::flush();
});

it('shows accurate dashboard cards and keeps admin cards from users', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $classification = Classification::factory()->create(['code' => '500']);
    $incoming = IncomingLetter::factory()->create([
        'registered_by' => $admin->getKey(),
        'classification_id' => $classification->getKey(),
        'letter_date' => today()->toDateString(),
    ]);
    OutgoingLetter::factory()->create([
        'created_by' => $admin->getKey(),
        'classification_id' => $classification->getKey(),
        'letter_date' => today()->toDateString(),
    ]);
    Disposition::query()->create([
        'incoming_letter_id' => $incoming->getKey(),
        'nature' => DispositionNature::Biasa,
        'forwarded_to' => ['Direksi'],
        'instructions' => ['untuk_diketahui'],
        'disposition_date' => today()->toDateString(),
        'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin);
    $adminCards = Livewire::test(SummaryCardsWidget::class)->instance()->getCards();
    $adminCards = collect($adminCards)->keyBy('label');

    expect($adminCards)->toHaveCount(6)
        ->and($adminCards[__('app.incoming_total')]['value'])->toBe(1)
        ->and($adminCards[__('app.outgoing_total')]['value'])->toBe(1)
        ->and($adminCards[__('app.dispositions_done')]['value'])->toBe(1)
        ->and($adminCards[__('app.dispositions_pending')]['value'])->toBe(0)
        ->and($adminCards[__('app.classifications_total')]['value'])->toBe(1)
        ->and($adminCards[__('app.users_total')]['value'])->toBe(1);

    $user = User::factory()->create(['level' => UserLevel::User]);
    $this->actingAs($user);
    $userCards = Livewire::test(SummaryCardsWidget::class)->instance()->getCards();

    expect($userCards)->toHaveCount(4)
        ->and(collect($userCards)->pluck('label')->all())->not->toContain(__('app.classifications_total'))
        ->and(collect($userCards)->pluck('label')->all())->not->toContain(__('app.users_total'));
});

it('builds monthly and classification charts from actual letter records', function (): void {
    $classification = Classification::factory()->create(['code' => '500', 'name' => 'Perekonomian']);
    IncomingLetter::factory()->create([
        'classification_id' => $classification->getKey(),
        'letter_date' => today()->toDateString(),
    ]);
    OutgoingLetter::factory()->create([
        'classification_id' => $classification->getKey(),
        'letter_date' => today()->toDateString(),
    ]);

    $monthly = Livewire::test(LettersByMonthChart::class)->instance()->getChartData();
    $classificationChart = Livewire::test(LettersByClassificationChart::class)->instance()->getChartData();

    expect($monthly['labels'])->toHaveCount(6)
        ->and($monthly['datasets'][0]['data'][5])->toBe(1)
        ->and($monthly['datasets'][1]['data'][5])->toBe(1)
        ->and($classificationChart['labels'])->toBe(['500 — Perekonomian'])
        ->and($classificationChart['datasets'][0]['data'])->toBe([2]);
});

it('shows zero summaries and friendly empty chart data when there are no letters', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $this->actingAs($admin);

    $cards = collect(Livewire::test(SummaryCardsWidget::class)->instance()->getCards())->keyBy('label');
    $chart = Livewire::test(LettersByClassificationChart::class)->instance();

    expect($cards[__('app.incoming_total')]['value'])->toBe(0)
        ->and($cards[__('app.outgoing_total')]['value'])->toBe(0)
        ->and($cards[__('app.dispositions_done')]['value'])->toBe(0)
        ->and($cards[__('app.dispositions_pending')]['value'])->toBe(0)
        ->and($chart->getChartData()['labels'])->toBe([])
        ->and($chart->getDescription())->toBe(__('app.no_data'));
});

it('adds security headers to application responses', function (): void {
    $this->get('/up')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
