<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->json('deployment_detail_ids')->nullable()->after('description')->comment('IDs of operation_deployment_details this payment covers');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropColumn('deployment_detail_ids');
        });
    }
};
