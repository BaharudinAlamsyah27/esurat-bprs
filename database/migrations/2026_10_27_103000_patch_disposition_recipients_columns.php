<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disposition_recipients')) {
            Schema::table('disposition_recipients', function (Blueprint $table): void {
                if (! Schema::hasColumn('disposition_recipients', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('disposition_id');
                }
                if (! Schema::hasColumn('disposition_recipients', 'done_note')) {
                    $table->string('done_note', 255)->nullable()->after('done_at');
                }
                if (! Schema::hasColumn('disposition_recipients', 'notified_at')) {
                    $table->timestamp('notified_at')->nullable()->after('done_note');
                }
            });

            // Backfill user_id from recipient_user_id if present
            if (Schema::hasColumn('disposition_recipients', 'recipient_user_id')) {
                DB::table('disposition_recipients')
                    ->whereNull('user_id')
                    ->whereNotNull('recipient_user_id')
                    ->update(['user_id' => DB::raw('recipient_user_id')]);
            }

            // Backfill done_note from report_notes if present
            if (Schema::hasColumn('disposition_recipients', 'report_notes')) {
                DB::table('disposition_recipients')
                    ->whereNull('done_note')
                    ->whereNotNull('report_notes')
                    ->update(['done_note' => DB::raw('report_notes')]);
            }
        }
    }

    public function down(): void
    {
        // Safe no-op
    }
};
