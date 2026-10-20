<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Position> */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'level' => 99,
            'is_signer_eligible' => false,
        ];
    }
}
