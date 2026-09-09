<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            // Add missing columns for deployment details
            if (!Schema::hasColumn('operation_deployment_details', 'deployment_from_date')) {
                $table->dateTime('deployment_from_date')->nullable()->after('operation_lead_id');
            }

            if (!Schema::hasColumn('operation_deployment_details', 'deployment_to_date')) {
                $table->dateTime('deployment_to_date')->nullable()->after('deployment_from_date');
            }

            if (!Schema::hasColumn('operation_deployment_details', 'duty_hours')) {
                $table->enum('duty_hours', ['12hr', '24hr'])->nullable()->after('deployment_status');
            }

            if (!Schema::hasColumn('operation_deployment_details', 'remark')) {
                $table->text('remark')->nullable()->after('vendor_rate_per_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            $table->dropColumn(['deployment_from_date', 'deployment_to_date', 'duty_hours', 'remark']);
        });
    }
};
