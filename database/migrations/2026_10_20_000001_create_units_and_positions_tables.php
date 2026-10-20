<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('lainnya');
            $table->foreignId('parent_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('level')->default(99);
            $table->boolean('is_signer_eligible')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
        Schema::dropIfExists('units');
    }
};
