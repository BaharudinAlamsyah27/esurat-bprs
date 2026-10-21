<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class LetterSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->firstOrCreate(
            ['key' => 'outgoing_number_pattern'],
            ['value' => ['pattern' => config('letters.outgoing_number_pattern')]],
        );
    }
}
