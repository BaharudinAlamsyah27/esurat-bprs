<?php

namespace Database\Factories;

use App\Enums\DispositionRecipientStatus;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DispositionRecipient> */
class DispositionRecipientFactory extends Factory
{
    protected $model = DispositionRecipient::class;

    public function definition(): array
    {
        return [
            'disposition_id' => Disposition::factory(),
            'user_id' => User::factory(),
            'status' => DispositionRecipientStatus::New->value,
            'read_at' => null,
            'done_at' => null,
            'done_note' => null,
            'notified_at' => now(),
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => DispositionRecipientStatus::Done->value,
            'read_at' => now(),
            'done_at' => now(),
            'done_note' => 'Sudah ditindaklanjuti.',
        ]);
    }
}
