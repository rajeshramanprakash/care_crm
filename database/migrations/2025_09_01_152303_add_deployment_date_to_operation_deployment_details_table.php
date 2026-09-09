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
            // Add deployment_date column if it doesn't exist
            if (!Schema::hasColumn('operation_deployment_details', 'deployment_date')) {
                $table->dateTime('deployment_date')->nullable()->after('operation_lead_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            $table->dropColumn('deployment_date');
        });
    }
};
