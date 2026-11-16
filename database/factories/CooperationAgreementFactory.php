<?php

namespace Database\Factories;

use App\Models\CooperationAgreement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CooperationAgreement> */
class CooperationAgreementFactory extends Factory
{
    protected $model = CooperationAgreement::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'bss_pks_number' => sprintf('%03d/PKS/BPRS-SRG/%s', fake()->numberBetween(1, 999), date('Y')),
            'partner_pks_number' => sprintf('%03d/MITRA/%s', fake()->numberBetween(1, 999), date('Y')),
            'partner_npwp' => fake()->numerify('##.###.###.#-###.###'),
            'agreement_date' => fake()->date(),
            'partner_name' => fake()->company(),
            'subject' => fake()->sentence(5),
            'pic_name' => fake()->name(),
            'note' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
