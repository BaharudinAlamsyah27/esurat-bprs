<?php

namespace Database\Factories;

use App\Models\DispositionInstruction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DispositionInstruction> */
class DispositionInstructionFactory extends Factory
{
    protected $model = DispositionInstruction::class;

    public function definition(): array
    {
        return [
            'text' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 99),
            'is_active' => true,
        ];
    }
}
