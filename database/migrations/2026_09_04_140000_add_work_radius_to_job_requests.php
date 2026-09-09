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

        $afterCol = null;
        foreach (['full_address_lng', 'full_address', 'location'] as $candidate) {
            if (Schema::hasColumn('job_requests', $candidate)) {
                $afterCol = $candidate;
                break;
            }
        }

        Schema::table('job_requests', function (Blueprint $table) use ($afterCol) {
            if (! Schema::hasColumn('job_requests', 'radius_12hr_km')) {
                $col = $table->decimal('radius_12hr_km', 8, 2)->nullable();
                if ($afterCol) {
                    $col->after($afterCol);
                }
            }
        });

        Schema::table('job_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('job_requests', 'radius_24hr_km')) {
                $after = Schema::hasColumn('job_requests', 'radius_12hr_km') ? 'radius_12hr_km' : null;
                $col = $table->decimal('radius_24hr_km', 8, 2)->nullable();
                if ($after) {
                    $col->after($after);
                }
            }
        });

        Schema::table('job_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('job_requests', 'radius_onetime_km')) {
                $after = Schema::hasColumn('job_requests', 'radius_24hr_km') ? 'radius_24hr_km' : null;
                $col = $table->decimal('radius_onetime_km', 8, 2)->nullable();
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
            foreach (['radius_12hr_km', 'radius_24hr_km', 'radius_onetime_km'] as $col) {
                if (Schema::hasColumn('job_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
