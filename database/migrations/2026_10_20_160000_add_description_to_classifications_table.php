<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('classifications', 'description')) {
            Schema::table('classifications', function (Blueprint $table): void {
                $table->text('description')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('classifications', 'description')) {
            Schema::table('classifications', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }
    }
};
