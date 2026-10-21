<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('level', 16)->default('user')->after('password');
            });
        }

        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('email')->nullable()->change();
            });
        }

        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $adminRoleIds = DB::table('roles')->whereIn('name', ['super_admin', 'admin_persuratan'])->pluck('id');

            if ($adminRoleIds->isNotEmpty()) {
                $adminUserIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('role_id', $adminRoleIds)
                    ->pluck('model_id');

                if ($adminUserIds->isNotEmpty()) {
                    DB::table('users')->whereIn('id', $adminUserIds)->update(['level' => 'admin']);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('level');
            });
        }
    }
};
