<?php

namespace Database\Factories;

use App\Models\LetterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LetterType> */
class LetterTypeFactory extends Factory
{
    protected $model = LetterType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->bothify('JNS-####'),
            'category' => 'both',
            'is_active' => true,
        ];
    }
}
