<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('dispositions')->restrictOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('urgency', 30)->default('biasa');
            $table->timestamp('due_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['incoming_letter_id', 'parent_id']);
        });

        Schema::create('disposition_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('disposition_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_unit_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->text('report_notes')->nullable();
            $table->foreignId('acting_for_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['recipient_user_id', 'status']);
            $table->index(['recipient_unit_id', 'status']);
        });

        Schema::create('disposition_disposition_instruction', function (Blueprint $table): void {
            $table->foreignId('disposition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disposition_instruction_id')->constrained(indexName: 'disposition_instruction_id_foreign')->restrictOnDelete();
            $table->primary(['disposition_id', 'disposition_instruction_id'], 'disposition_instruction_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposition_disposition_instruction');
        Schema::dropIfExists('disposition_recipients');
        Schema::dropIfExists('dispositions');
    }
};
