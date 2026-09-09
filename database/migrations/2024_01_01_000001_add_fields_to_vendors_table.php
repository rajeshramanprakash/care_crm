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
        Schema::table('vendors', function (Blueprint $table) {
            // Account Details
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('upi_id')->nullable();

            // Service and Location
            $table->json('services')->nullable(); // Multiple services
            $table->json('cities')->nullable(); // Multiple cities

            // Shift and Status
            $table->enum('shift', ['12', '24', 'both'])->nullable();
            $table->enum('status', ['active', 'dutyoff'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'account_number',
                'ifsc_code',
                'upi_id',
                'services',
                'cities',
                'shift',
                'status'
            ]);
        });
    }
};
