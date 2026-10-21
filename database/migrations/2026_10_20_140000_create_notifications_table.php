<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** New v2 installs do not use notifications. */
    public function up(): void
    {
        // No-op after the v2 simplification.
    }

    /** Existing notification tables are retained for data safety. */
    public function down(): void
    {
        // No-op.
    }
};
