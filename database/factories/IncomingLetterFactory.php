<?php

namespace Database\Factories;

use App\Enums\IncomingDeliveryMethod;
use App\Enums\IncomingLetterStatus;
use App\Enums\IncomingLetterUrgency;
use App\Enums\LetterConfidentiality;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\LetterType;
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
            'letter_number' => fake()->optional()->bothify('###/???/##'),
            'sender_name' => fake()->name(),
            'sender_institution' => fake()->company(),
            'subject' => fake()->sentence(5),
            'summary' => fake()->optional()->paragraph(),
            'letter_type_id' => LetterType::factory(),
            'classification_id' => Classification::factory(),
            'urgency' => IncomingLetterUrgency::Normal,
            'confidentiality' => LetterConfidentiality::Ordinary,
            'delivery_method' => IncomingDeliveryMethod::Direct,
            'attachments_count' => 0,
            'status' => IncomingLetterStatus::Registered,
            'registered_by' => User::factory(),
        ];
    }
}
