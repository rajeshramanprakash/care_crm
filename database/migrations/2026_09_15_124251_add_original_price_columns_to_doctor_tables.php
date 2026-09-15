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
        Schema::table('location_doctor_consultation_prices', function (Blueprint $table) {
            $table->decimal('original_website_price', 10, 2)->nullable()->after('website_price');
            $table->decimal('original_doctor_max_price', 10, 2)->nullable()->after('doctor_max_price');
        });

        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->decimal('original_online_charges', 10, 2)->nullable()->after('online_charges');
            $table->decimal('original_home_visit_charges', 10, 2)->nullable()->after('home_visit_charges');
            $table->decimal('original_clinic_consultation_charges', 10, 2)->nullable()->after('clinic_consultation_charges');
            
            $table->decimal('original_website_customer_fee_online', 10, 2)->nullable()->after('website_customer_fee_online');
            $table->decimal('original_website_customer_fee_home_visit', 10, 2)->nullable()->after('website_customer_fee_home_visit');
            $table->decimal('original_website_customer_fee_clinic', 10, 2)->nullable()->after('website_customer_fee_clinic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_doctor_consultation_prices', function (Blueprint $table) {
            $table->dropColumn(['original_website_price', 'original_doctor_max_price']);
        });

        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'original_online_charges',
                'original_home_visit_charges',
                'original_clinic_consultation_charges',
                'original_website_customer_fee_online',
                'original_website_customer_fee_home_visit',
                'original_website_customer_fee_clinic'
            ]);
        });
    }
};
