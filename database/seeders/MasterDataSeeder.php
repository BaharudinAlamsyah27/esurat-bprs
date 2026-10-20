<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\DispositionInstruction;
use App\Models\LetterType;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Direksi', 'code' => 'DIR', 'type' => 'direksi'],
            ['name' => 'Operasional', 'code' => 'OPS', 'type' => 'divisi'],
            ['name' => 'Pembiayaan', 'code' => 'PEM', 'type' => 'divisi'],
            ['name' => 'Dana & Jasa', 'code' => 'DJS', 'type' => 'divisi'],
            ['name' => 'Kepatuhan', 'code' => 'KEP', 'type' => 'divisi'],
            ['name' => 'Cabang Sragen', 'code' => 'CAB-SRG', 'type' => 'cabang'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['code' => $unit['code']], $unit + ['is_active' => true]);
        }

        $positions = [
            ['name' => 'Direktur Utama', 'level' => 1, 'is_signer_eligible' => true],
            ['name' => 'Direktur', 'level' => 2, 'is_signer_eligible' => true],
            ['name' => 'Kepala Unit', 'level' => 3, 'is_signer_eligible' => false],
            ['name' => 'Staf', 'level' => 4, 'is_signer_eligible' => false],
            ['name' => 'Auditor', 'level' => 5, 'is_signer_eligible' => false],
            ['name' => 'Admin Persuratan', 'level' => 5, 'is_signer_eligible' => false],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(['name' => $position['name']], $position);
        }

        foreach ([
            ['code' => 'UMUM', 'name' => 'Umum'],
            ['code' => 'KEU', 'name' => 'Keuangan'],
            ['code' => 'SDM', 'name' => 'Sumber Daya Manusia'],
        ] as $classification) {
            Classification::updateOrCreate(['code' => $classification['code']], $classification + ['is_active' => true]);
        }

        foreach ([
            ['code' => 'DINAS', 'name' => 'Surat Dinas', 'category' => 'both'],
            ['code' => 'UND', 'name' => 'Undangan', 'category' => 'both'],
            ['code' => 'KET', 'name' => 'Surat Keterangan', 'category' => 'both'],
        ] as $letterType) {
            LetterType::updateOrCreate(['code' => $letterType['code']], $letterType + ['is_active' => true]);
        }

        foreach ([
            'Untuk diketahui',
            'Segera tindak lanjuti',
            'Koordinasikan',
            'Siapkan jawaban',
            'Arsipkan',
        ] as $index => $instruction) {
            DispositionInstruction::updateOrCreate(
                ['text' => $instruction],
                ['sort_order' => $index + 1, 'is_active' => true],
            );
        }

        foreach ([
            'session_idle_timeout_minutes' => 30,
            'institution_name' => 'BPRS Sragen',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_THROW_ON_ERROR)]);
        }
    }
}
