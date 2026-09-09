<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_website_bookings', 'payment_status')) {
                $table->string('payment_status', 32)->default('paid')->after('booking_fee_amount');
            }
            if (! Schema::hasColumn('consultation_website_bookings', 'easebuzz_txnid')) {
                $table->string('easebuzz_txnid', 64)->nullable()->unique()->after('payment_status');
            }
            if (! Schema::hasColumn('consultation_website_bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('easebuzz_txnid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            foreach (['paid_at', 'easebuzz_txnid', 'payment_status'] as $col) {
                if (Schema::hasColumn('consultation_website_bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
