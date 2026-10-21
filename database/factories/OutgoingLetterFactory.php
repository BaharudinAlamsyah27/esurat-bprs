<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OutgoingLetter> */
class OutgoingLetterFactory extends Factory
{
    protected $model = OutgoingLetter::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $sequence = fake()->unique()->numberBetween(1, 999999);

        return [
            'agenda_year' => $year,
            'agenda_seq' => $sequence,
            'agenda_number' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT).'/'.$year,
            'letter_number' => fake()->unique()->bothify('###/###/BPRS-SRG/??/####'),
            'recipient' => fake()->company(),
            'subject' => fake()->sentence(5),
            'note' => fake()->optional()->paragraph(),
            'classification_id' => Classification::factory(),
            'letter_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'sent_date' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
