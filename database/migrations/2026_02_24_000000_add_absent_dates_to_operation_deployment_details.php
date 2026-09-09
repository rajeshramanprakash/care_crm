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
            // Store absent dates as JSON array of YYYY-MM-DD strings
            if (!Schema::hasColumn('operation_deployment_details', 'absent_dates')) {
                $table->json('absent_dates')->nullable()->after('vendor_payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            if (Schema::hasColumn('operation_deployment_details', 'absent_dates')) {
                $table->dropColumn('absent_dates');
            }
        });
    }
};

