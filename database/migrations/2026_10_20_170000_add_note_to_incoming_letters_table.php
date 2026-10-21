<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('incoming_letters', 'note')) {
            Schema::table('incoming_letters', function (Blueprint $table): void {
                $table->text('note')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep user notes intact when rolling back a later migration.
    }
};
