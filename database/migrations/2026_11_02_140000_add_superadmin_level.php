<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the existing admin accounts; promote one deterministic account to Superadmin.
        $admins = DB::table('users')->where('level', 'admin');

        if (Schema::hasColumn('users', 'deleted_at')) {
            $admins->whereNull('deleted_at');
        }

        $adminIds = (clone $admins)->orderBy('id')->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $preferredAdmin = (clone $admins)
            ->where('is_active', true)
            ->where('username', 'superadmin')
            ->value('id');

        $superAdminId = $preferredAdmin
            ?? (clone $admins)->where('is_active', true)->orderBy('id')->value('id')
            ?? $adminIds->first();

        DB::table('users')->where('id', $superAdminId)->update(['level' => 'superadmin']);
    }

    public function down(): void
    {
        DB::table('users')->where('level', 'superadmin')->update(['level' => 'admin']);
    }
};
