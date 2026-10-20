<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->foreignId('head_user_id')->nullable()->after('parent_id')->constrained('users')->restrictOnDelete();
        });

        Schema::create('classifications', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('classifications')->restrictOnDelete();
            $table->unsignedSmallInteger('retention_years')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('letter_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category');
            $table->unsignedBigInteger('default_approval_flow_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('disposition_instructions', function (Blueprint $table): void {
            $table->id();
            $table->string('text');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('disposition_instructions');
        Schema::dropIfExists('letter_types');
        Schema::dropIfExists('classifications');

        Schema::table('units', function (Blueprint $table): void {
            $table->dropForeign(['head_user_id']);
            $table->dropColumn('head_user_id');
        });
    }
};
