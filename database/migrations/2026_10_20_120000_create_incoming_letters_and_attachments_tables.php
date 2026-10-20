<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 40);
            $table->string('scope_key', 40);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['category', 'scope_key']);
        });

        Schema::create('incoming_letters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('agenda_year');
            $table->unsignedInteger('agenda_seq');
            $table->string('agenda_number')->unique();
            $table->date('received_at');
            $table->date('letter_date');
            $table->string('letter_number')->nullable();
            $table->string('sender_name');
            $table->string('sender_institution')->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->foreignId('letter_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('classification_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('urgency', 30)->default('biasa');
            $table->string('confidentiality', 30)->default('biasa');
            $table->string('delivery_method', 30)->default('langsung');
            $table->unsignedSmallInteger('attachments_count')->default(0);
            $table->string('status', 30)->default('registered');
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('initial_recipient_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->string('archive_location')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['agenda_year', 'agenda_seq']);
            $table->index(['status', 'received_at']);
            $table->index(['confidentiality', 'status']);
            $table->index('letter_number');
            $table->index('sender_name');
            $table->index('subject');
        });

        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->string('disk', 40)->default('private');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('kind', 40)->default('scan');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('incoming_letters');
        Schema::dropIfExists('letter_sequences');
    }
};
