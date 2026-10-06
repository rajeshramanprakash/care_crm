<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Comma-separated location ids; "All Locations" (~5000 ids) does not fit in varchar(255).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY location_id TEXT NULL');
    }

    public function down(): void
    {
        // Shrinking back to varchar(255) would truncate saved location lists.
    }
};
