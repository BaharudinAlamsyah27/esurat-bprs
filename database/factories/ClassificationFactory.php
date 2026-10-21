<?php

namespace Database\Factories;

use App\Models\Classification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Classification> */
class ClassificationFactory extends Factory
{
    protected $model = Classification::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('KLS-####'),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
