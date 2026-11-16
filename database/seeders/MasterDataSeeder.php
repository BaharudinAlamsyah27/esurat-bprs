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
            ['code' => '100', 'name' => 'Umum', 'description' => 'Surat umum, kesekretariatan, undangan, dan pemberitahuan'],
            ['code' => '200', 'name' => 'Politik', 'description' => 'Hubungan antarlembaga, asosiasi perbankan, dan organisasi'],
            ['code' => '300', 'name' => 'Pemerintahan', 'description' => 'Surat dari pemerintah daerah, kementerian, atau instansi publik'],
            ['code' => '400', 'name' => 'Kesejahteraan Rakyat', 'description' => 'CSR, kegiatan sosial, pendidikan, dan kemasyarakatan'],
            ['code' => '500', 'name' => 'Perekonomian', 'description' => 'Pembiayaan, nasabah, kemitraan usaha, dan perjanjian kerjasama'],
            ['code' => '600', 'name' => 'Pekerjaan Umum & Ketenagakerjaan', 'description' => 'Sarana prasarana, aset gedung, pengadaan, dan ketenagakerjaan'],
            ['code' => '700', 'name' => 'Pengawasan', 'description' => 'Audit internal, kepatuhan, regulasi OJK/BI, dan pemeriksaan'],
            ['code' => '800', 'name' => 'Kepegawaian', 'description' => 'SDM, pengangkatan, mutasi, cuti, dan pembinaan pegawai'],
            ['code' => '900', 'name' => 'Keuangan', 'description' => 'Akuntansi, kas, perpajakan, anggaran, dan laporan keuangan'],
        ] as $classification) {
            Classification::query()->updateOrCreate(
                ['code' => $classification['code']],
                ['name' => $classification['name'], 'description' => $classification['description']],
            );
        }

        Institution::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'BPRS Sragen', 'city' => 'Sragen'],
        );
    }
}
