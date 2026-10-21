<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $legacyDefaults = [
            'UMUM' => ['old_name' => 'Umum', 'new_code' => '100', 'new_name' => 'Umum'],
            'KEU' => ['old_name' => 'Keuangan', 'new_code' => '900', 'new_name' => 'Keuangan'],
            'SDM' => ['old_name' => 'Sumber Daya Manusia', 'new_code' => '800', 'new_name' => 'Kepegawaian'],
        ];

        foreach ($legacyDefaults as $oldCode => $target) {
            $legacy = Classification::query()
                ->where('code', $oldCode)
                ->where('name', $target['old_name'])
                ->first();

            if ($legacy && ! Classification::query()->where('code', $target['new_code'])->exists()) {
                $legacy->update(['code' => $target['new_code'], 'name' => $target['new_name']]);
            }
        }

        foreach ([
            ['code' => '100', 'name' => 'Umum'],
            ['code' => '200', 'name' => 'Politik'],
            ['code' => '300', 'name' => 'Pemerintahan'],
            ['code' => '400', 'name' => 'Kesejahteraan Rakyat'],
            ['code' => '500', 'name' => 'Perekonomian'],
            ['code' => '600', 'name' => 'Pekerjaan Umum & Ketenagakerjaan'],
            ['code' => '700', 'name' => 'Pengawasan'],
            ['code' => '800', 'name' => 'Kepegawaian'],
            ['code' => '900', 'name' => 'Keuangan'],
        ] as $classification) {
            Classification::query()->firstOrCreate(
                ['code' => $classification['code']],
                ['name' => $classification['name']],
            );
        }

        Institution::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'BPRS Sragen', 'city' => 'Sragen'],
        );
    }
}
