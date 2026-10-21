<?php

namespace Database\Seeders;

use App\Enums\UserLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Validation\Rules\Password;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::query()->where('level', UserLevel::Admin->value)->exists()) {
            $username = trim((string) env('INITIAL_ADMIN_USERNAME', 'admin'));
            $name = trim((string) env('INITIAL_ADMIN_NAME', 'Admin E-Surat'));
            $password = (string) env('INITIAL_ADMIN_PASSWORD', '');

            if ($username === '' || $name === '' || ! Password::min(10)->mixedCase()->numbers()->passes('password', $password)) {
                throw new \RuntimeException(__('app.initial_admin_password_required'));
            }

            User::query()->firstOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'level' => UserLevel::Admin,
                    'is_active' => true,
                    'password' => $password,
                ],
            );
        }

        if (app()->environment('local')) {
            $demoUsername = trim((string) env('LOCAL_DEMO_USERNAME', 'user'));
            $demoPassword = (string) env('LOCAL_DEMO_PASSWORD', '');

            if ($demoUsername !== '' && Password::min(10)->mixedCase()->numbers()->passes('password', $demoPassword)) {
                User::query()->firstOrCreate(
                    ['username' => $demoUsername],
                    [
                        'name' => trim((string) env('LOCAL_DEMO_NAME', 'Pengguna Demo')),
                        'level' => UserLevel::User,
                        'is_active' => true,
                        'password' => $demoPassword,
                    ],
                );
            }
        }
    }
}
