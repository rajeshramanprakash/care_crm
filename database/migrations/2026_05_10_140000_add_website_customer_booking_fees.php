<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_requests', 'website_customer_fee_online')) {
                $table->decimal('website_customer_fee_online', 12, 2)->nullable()->after('clinic_consultation_charges');
            }
            if (! Schema::hasColumn('doctor_requests', 'website_customer_fee_home_visit')) {
                $table->decimal('website_customer_fee_home_visit', 12, 2)->nullable()->after('website_customer_fee_online');
            }
            if (! Schema::hasColumn('doctor_requests', 'website_customer_fee_clinic')) {
                $table->decimal('website_customer_fee_clinic', 12, 2)->nullable()->after('website_customer_fee_home_visit');
            }
        });

        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_website_bookings', 'booking_fee_amount')) {
                $table->decimal('booking_fee_amount', 12, 2)->nullable()->after('consultation_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            foreach (['website_customer_fee_clinic', 'website_customer_fee_home_visit', 'website_customer_fee_online'] as $col) {
                if (Schema::hasColumn('doctor_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('consultation_website_bookings', 'booking_fee_amount')) {
                $table->dropColumn('booking_fee_amount');
            }
        });
    }
};
