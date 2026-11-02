<?php

namespace Database\Factories;

use App\Enums\DispositionInstruction;
use App\Enums\DispositionNature;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Disposition> */
class DispositionFactory extends Factory
{
    protected $model = Disposition::class;

    public function definition(): array
    {
        return [
            'incoming_letter_id' => IncomingLetter::factory(),
            'nature' => DispositionNature::Biasa->value,
            'forwarded_to' => ['Kepala Operasional'],
            'instructions' => [DispositionInstruction::UntukDiketahui->value],
            'instruction_other' => null,
            'note' => 'Mohon ditindaklanjuti.',
            'disposition_date' => now()->toDateString(),
            'print_count' => 0,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
