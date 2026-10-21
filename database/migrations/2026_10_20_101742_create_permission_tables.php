<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Legacy permission tables are intentionally not created for new installs.
     * Existing databases keep their tables and user records intact.
     */
    public function up(): void
    {
        // No-op after the v2 simplification.
    }

    public function down(): void
    {
        // No-op: never remove legacy authorization data automatically.
    }
};
