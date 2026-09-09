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
            // Add vendor payment field for calculated payment
            if (!Schema::hasColumn('operation_deployment_details', 'vendor_payment')) {
                $table->decimal('vendor_payment', 10, 2)->nullable()->after('vendor_rate_per_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            $table->dropColumn('vendor_payment');
        });
    }
};
