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
            $table->dateTime('deployment_from_date')->nullable()->after('operation_lead_id');
            $table->dateTime('deployment_to_date')->nullable()->after('deployment_from_date');
            $table->enum('duty_hours', ['12hr', '24hr'])->nullable()->after('deployment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            $table->dropColumn(['deployment_from_date', 'deployment_to_date', 'duty_hours']);
        });
    }
};
