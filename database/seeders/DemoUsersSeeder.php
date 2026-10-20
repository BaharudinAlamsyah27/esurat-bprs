<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $password = (string) env('DEMO_USER_PASSWORD', '');

        if (! Password::min(10)->mixedCase()->numbers()->passes('password', $password)) {
            throw new \RuntimeException(__('m1.demo_password_required'));
        }

        $direksi = Unit::query()->where('code', 'DIR')->firstOrFail();
        $operasional = Unit::query()->where('code', 'OPS')->firstOrFail();
        $direktur = Position::query()->where('name', 'Direktur Utama')->firstOrFail();
        $kepalaUnit = Position::query()->where('name', 'Kepala Unit')->firstOrFail();
        $staf = Position::query()->where('name', 'Staf')->firstOrFail();

        $users = [
            ['name' => 'Super Admin Demo', 'username' => 'superadmin', 'email' => 'superadmin@esurat.test', 'role' => 'super_admin', 'unit_id' => null, 'position_id' => null],
            ['name' => 'Admin Persuratan Demo', 'username' => 'admin.surat', 'email' => 'admin.surat@esurat.test', 'role' => 'admin_persuratan', 'unit_id' => $operasional->id, 'position_id' => Position::query()->where('name', 'Admin Persuratan')->value('id')],
            ['name' => 'Direksi Demo', 'username' => 'direksi', 'email' => 'direksi@esurat.test', 'role' => 'direksi', 'unit_id' => $direksi->id, 'position_id' => $direktur->id],
            ['name' => 'Kepala Operasional Demo', 'username' => 'kepala.ops', 'email' => 'kepala.ops@esurat.test', 'role' => 'kepala_unit', 'unit_id' => $operasional->id, 'position_id' => $kepalaUnit->id],
            ['name' => 'Staf Operasional Demo', 'username' => 'staf.ops', 'email' => 'staf.ops@esurat.test', 'role' => 'staf', 'unit_id' => $operasional->id, 'position_id' => $staf->id],
            ['name' => 'Staf Pembiayaan Demo', 'username' => 'staf.pem', 'email' => 'staf.pem@esurat.test', 'role' => 'staf', 'unit_id' => Unit::query()->where('code', 'PEM')->value('id'), 'position_id' => $staf->id],
            ['name' => 'Staf Dana Jasa Demo', 'username' => 'staf.djs', 'email' => 'staf.djs@esurat.test', 'role' => 'staf', 'unit_id' => Unit::query()->where('code', 'DJS')->value('id'), 'position_id' => $staf->id],
            ['name' => 'Auditor Demo', 'username' => 'auditor', 'email' => 'auditor@esurat.test', 'role' => 'auditor', 'unit_id' => null, 'position_id' => Position::query()->where('name', 'Auditor')->value('id')],
        ];

        foreach ($users as $attributes) {
            $role = $attributes['role'];
            unset($attributes['role']);

            $user = User::updateOrCreate(
                ['email' => $attributes['email']],
                $attributes + [
                    'nip' => 'DEMO-'.Str::upper($attributes['username']),
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$role]);
        }

        Unit::query()->where('code', 'DIR')->update(['head_user_id' => User::query()->where('username', 'direksi')->value('id')]);
        Unit::query()->where('code', 'OPS')->update(['head_user_id' => User::query()->where('username', 'kepala.ops')->value('id')]);
    }
}
