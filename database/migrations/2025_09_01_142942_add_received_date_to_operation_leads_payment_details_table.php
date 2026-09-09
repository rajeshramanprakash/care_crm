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
            // Add missing columns
            if (!Schema::hasColumn('operation_leads_payment_details', 'received_date')) {
                $table->dateTime('received_date')->nullable()->after('utr_number');
            }

            if (!Schema::hasColumn('operation_leads_payment_details', 'from_date_time')) {
                $table->dateTime('from_date_time')->nullable()->after('received_date');
            }

            if (!Schema::hasColumn('operation_leads_payment_details', 'to_date_time')) {
                $table->dateTime('to_date_time')->nullable()->after('from_date_time');
            }

            if (!Schema::hasColumn('operation_leads_payment_details', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->default(0)->after('payment_received');
            }

            if (!Schema::hasColumn('operation_leads_payment_details', 'remark')) {
                $table->text('remark')->nullable()->after('outstanding_payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_leads_payment_details', function (Blueprint $table) {
            $table->dropColumn(['received_date', 'from_date_time', 'to_date_time', 'refund_amount', 'remark']);
        });
    }
};
