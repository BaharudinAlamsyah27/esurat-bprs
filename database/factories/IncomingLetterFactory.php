<?php

namespace Database\Factories;

use App\Enums\IncomingLetterStatus;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IncomingLetter> */
class IncomingLetterFactory extends Factory
{
    protected $model = IncomingLetter::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $sequence = fake()->unique()->numberBetween(1, 999999);

        return [
            'agenda_year' => $year,
            'agenda_seq' => $sequence,
            'agenda_number' => str_pad((string) $sequence, 3, '0', STR_PAD_LEFT).'/'.$year,
            'received_at' => now()->toDateString(),
            'letter_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'letter_number' => fake()->bothify('###/???/##'),
            'sender_name' => fake()->name(),
            'subject' => fake()->sentence(5),
            'note' => fake()->optional()->paragraph(),
            'classification_id' => Classification::factory(),
            'attachments_count' => 0,
            'status' => IncomingLetterStatus::Registered->value,
            'registered_by' => User::factory(),
        ];
    }
}
