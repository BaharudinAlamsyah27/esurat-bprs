<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dispositions')) {
            if (! Schema::hasColumn('dispositions', 'parent_id') && ! Schema::hasColumn('dispositions', 'status')) {
                throw new RuntimeException('Table dispositions exists but is not the legacy workflow table; manual review is required.');
            }

            if (Schema::hasTable('legacy_dispositions')) {
                throw new RuntimeException('Both dispositions and legacy_dispositions exist; manual review is required to preserve both tables.');
            }

            Schema::rename('dispositions', 'legacy_dispositions');
        }

        Schema::create('dispositions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')
                ->unique('dispositions_m3_incoming_letter_unique')
                ->constrained('incoming_letters', 'id', 'dispositions_m3_incoming_letter_fk')
                ->restrictOnDelete();
            $table->string('nature', 30);
            $table->json('forwarded_to');
            $table->json('instructions');
            $table->string('instruction_other', 255)->nullable();
            $table->text('note')->nullable();
            $table->date('disposition_date');
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('created_by')->constrained('users', 'id', 'dispositions_m3_created_by_fk')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id', 'dispositions_m3_updated_by_fk')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_dispositions')) {
            if (Schema::hasTable('simple_dispositions_m3_archive')) {
                throw new RuntimeException('simple_dispositions_m3_archive already exists; migration rollback stopped to avoid data loss.');
            }

            Schema::rename('dispositions', 'simple_dispositions_m3_archive');
            Schema::rename('legacy_dispositions', 'dispositions');

            return;
        }

        Schema::rename('dispositions', 'simple_dispositions_m3_archive');
    }
};
