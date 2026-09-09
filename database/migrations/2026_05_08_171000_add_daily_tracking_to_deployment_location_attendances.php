<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deployment_location_attendances')) {
            return;
        }

        Schema::table('deployment_location_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_location_attendances', 'attendance_date')) {
                $table->date('attendance_date')->nullable()->after('freelancer_id');
            }
            if (!Schema::hasColumn('deployment_location_attendances', 'distance_meters')) {
                $table->decimal('distance_meters', 10, 2)->nullable()->after('attendance_status');
            }
            if (!Schema::hasColumn('deployment_location_attendances', 'is_location_matched')) {
                $table->boolean('is_location_matched')->default(false)->after('distance_meters');
            }
        });

        Schema::table('deployment_location_attendances', function (Blueprint $table) {
            $table->index(['attendance_date', 'operation_lead_id'], 'dla_date_lead_idx');
            $table->unique(['attendance_date', 'operation_lead_id', 'vendor_id', 'freelancer_id'], 'dla_daily_unique_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('deployment_location_attendances')) {
            return;
        }

        Schema::table('deployment_location_attendances', function (Blueprint $table) {
            $table->dropUnique('dla_daily_unique_idx');
            $table->dropIndex('dla_date_lead_idx');
            $table->dropColumn(['attendance_date', 'distance_meters', 'is_location_matched']);
        });
    }
};
