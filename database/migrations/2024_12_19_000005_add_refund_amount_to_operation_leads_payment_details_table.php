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
        Schema::table('operation_leads_payment_details', function (Blueprint $table) {
            $table->decimal('refund_amount', 10, 2)->nullable()->default(0)->after('payment_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_leads_payment_details', function (Blueprint $table) {
            $table->dropColumn('refund_amount');
        });
    }
};
