<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('nip')->nullable()->unique()->after('username');
            $table->string('phone', 30)->nullable()->after('email');
            $table->foreignId('unit_id')->nullable()->after('phone')->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->nullable()->after('unit_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->after('position_id');
            $table->string('signature_path')->nullable()->after('is_active');
            $table->string('paraf_path')->nullable()->after('signature_path');
            $table->timestamp('last_login_at')->nullable()->after('paraf_path');
            $table->string('ui_font_scale', 16)->default('normal')->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['position_id']);
            $table->dropUnique(['username']);
            $table->dropUnique(['nip']);
            $table->dropColumn([
                'username',
                'nip',
                'phone',
                'unit_id',
                'position_id',
                'is_active',
                'signature_path',
                'paraf_path',
                'last_login_at',
                'ui_font_scale',
                'deleted_at',
            ]);
        });
    }
};
