<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users table
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'position_title')) {
                $table->string('position_title', 100)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'push_enabled')) {
                $table->boolean('push_enabled')->default(true)->after('is_active');
            }
        });

        // 2. Create disposition_recipients table
        if (! Schema::hasTable('disposition_recipients')) {
            Schema::create('disposition_recipients', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('disposition_id')->constrained('dispositions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 20)->default('new');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('done_at')->nullable();
                $table->string('done_note', 255)->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();

                $table->unique(['disposition_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // 3. Create device_tokens table
        if (! Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('token', 512)->unique();
                $table->string('platform', 20)->default('android');
                $table->string('app_version', 20)->nullable();
                $table->string('device_name', 100)->nullable();
                $table->string('session_id', 100)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }

        // 4. Standard Laravel notifications table for database notifications
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('disposition_recipients');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'position_title')) {
                $table->dropColumn('position_title');
            }
            if (Schema::hasColumn('users', 'push_enabled')) {
                $table->dropColumn('push_enabled');
            }
        });
    }
};
