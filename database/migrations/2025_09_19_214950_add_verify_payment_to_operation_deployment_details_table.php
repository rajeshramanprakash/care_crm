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
            $table->boolean('verify_payment')->default(false)->after('vendor_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            $table->dropColumn('verify_payment');
        });
    }
};