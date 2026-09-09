<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_requests')) {
            return;
        }

        Schema::table('job_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('job_requests', 'full_address')) {
                $after = Schema::hasColumn('job_requests', 'location') ? 'location' : null;
                $col = $table->text('full_address')->nullable();
                if ($after) {
                    $col->after($after);
                }
            }
        });

        Schema::table('job_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('job_requests', 'full_address_lat')) {
                $after = Schema::hasColumn('job_requests', 'full_address') ? 'full_address' : null;
                $col = $table->decimal('full_address_lat', 10, 7)->nullable();
                if ($after) {
                    $col->after($after);
                }
            }
        });

        Schema::table('job_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('job_requests', 'full_address_lng')) {
                $after = Schema::hasColumn('job_requests', 'full_address_lat') ? 'full_address_lat' : null;
                $col = $table->decimal('full_address_lng', 10, 7)->nullable();
                if ($after) {
                    $col->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_requests')) {
            return;
        }

        Schema::table('job_requests', function (Blueprint $table) {
            foreach (['full_address_lng', 'full_address_lat', 'full_address'] as $col) {
                if (Schema::hasColumn('job_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
