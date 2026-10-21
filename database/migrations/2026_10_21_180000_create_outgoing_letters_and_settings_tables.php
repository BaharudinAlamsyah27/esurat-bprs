<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 120)->unique();
                $table->json('value');
                $table->timestamps();
            });
        }

        Schema::create('outgoing_letters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('agenda_year');
            $table->unsignedInteger('agenda_seq');
            $table->string('agenda_number')->unique();
            $table->string('letter_number')->unique();
            $table->string('recipient');
            $table->string('subject');
            $table->text('note')->nullable();
            $table->foreignId('classification_id')->constrained()->restrictOnDelete();
            $table->date('letter_date');
            $table->date('sent_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['agenda_year', 'agenda_seq']);
            $table->index('letter_date');
            $table->index('sent_date');
            $table->index('recipient');
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
        // The settings table may predate this migration and can contain other application settings.
    }
};
