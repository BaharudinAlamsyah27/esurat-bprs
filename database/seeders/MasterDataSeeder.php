<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'UMUM', 'name' => 'Umum'],
            ['code' => 'KEU', 'name' => 'Keuangan'],
            ['code' => 'SDM', 'name' => 'Sumber Daya Manusia'],
        ] as $classification) {
            Classification::query()->updateOrCreate(['code' => $classification['code']], $classification);
        }
    }
}
