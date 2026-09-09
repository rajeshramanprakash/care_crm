<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_requests') && Schema::hasColumn('job_requests', 'shift')) {
            DB::statement("ALTER TABLE job_requests MODIFY COLUMN shift ENUM('12', '24', 'both', 'onetime') NOT NULL");
        }

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'shift')) {
            DB::statement("ALTER TABLE vendors MODIFY COLUMN shift ENUM('12', '24', 'both', 'onetime') NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_requests') && Schema::hasColumn('job_requests', 'shift')) {
            DB::statement("ALTER TABLE job_requests MODIFY COLUMN shift ENUM('12', '24', 'both') NOT NULL");
        }

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'shift')) {
            DB::statement("ALTER TABLE vendors MODIFY COLUMN shift ENUM('12', '24', 'both') NULL");
        }
    }
};
