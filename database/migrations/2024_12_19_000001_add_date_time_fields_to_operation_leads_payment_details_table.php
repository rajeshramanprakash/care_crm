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
            $table->dateTime('from_date_time')->nullable()->after('utr_number');
            $table->dateTime('to_date_time')->nullable()->after('from_date_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_leads_payment_details', function (Blueprint $table) {
            $table->dropColumn(['from_date_time', 'to_date_time']);
        });
    }
};
