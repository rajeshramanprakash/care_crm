<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('operation_leads')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                if (! Schema::hasColumn('operation_leads', 'customer_location_attendance_enabled')) {
                    $table->boolean('customer_location_attendance_enabled')->default(false);
                }
            });
        }

        if (Schema::hasTable('deployment_location_attendances')) {
            Schema::table('deployment_location_attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('deployment_location_attendances', 'freelancer_selfie_path')) {
                    $table->string('freelancer_selfie_path', 512)->nullable();
                }
                if (! Schema::hasColumn('deployment_location_attendances', 'freelancer_selfie_captured_at')) {
                    $table->timestamp('freelancer_selfie_captured_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('operation_leads')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                if (Schema::hasColumn('operation_leads', 'customer_location_attendance_enabled')) {
                    $table->dropColumn('customer_location_attendance_enabled');
                }
            });
        }

        if (Schema::hasTable('deployment_location_attendances')) {
            Schema::table('deployment_location_attendances', function (Blueprint $table) {
                if (Schema::hasColumn('deployment_location_attendances', 'freelancer_selfie_path')) {
                    $table->dropColumn('freelancer_selfie_path');
                }
                if (Schema::hasColumn('deployment_location_attendances', 'freelancer_selfie_captured_at')) {
                    $table->dropColumn('freelancer_selfie_captured_at');
                }
            });
        }
    }
};
