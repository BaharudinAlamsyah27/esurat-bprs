<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** New v2 installs do not create organizational hierarchies. */
    public function up(): void
    {
        // No-op after the v2 simplification.
    }

    /** Existing tables are retained; this migration does not drop data. */
    public function down(): void
    {
        // No-op.
    }
};
