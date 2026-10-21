<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Simple dispositions are added in milestone M3. */
    public function up(): void
    {
        // No-op: this migration previously created a deprecated tracking workflow.
    }

    /** Existing legacy disposition data is retained; never drop it automatically. */
    public function down(): void
    {
        // No-op.
    }
};
