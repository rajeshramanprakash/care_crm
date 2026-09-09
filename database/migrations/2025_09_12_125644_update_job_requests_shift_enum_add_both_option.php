<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the shift enum to include 'both' option
        DB::statement("ALTER TABLE job_requests MODIFY COLUMN shift ENUM('12', '24', 'both') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE job_requests MODIFY COLUMN shift ENUM('12', '24') NOT NULL");
    }
};
