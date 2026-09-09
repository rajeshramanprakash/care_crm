<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->string('referral_commission_online_type', 16)->default('fixed')->after('referral_commission_online');
            $table->string('referral_commission_home_visit_type', 16)->default('fixed')->after('referral_commission_home_visit');
            $table->string('referral_commission_clinic_type', 16)->default('fixed')->after('referral_commission_clinic');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'referral_commission_online_type',
                'referral_commission_home_visit_type',
                'referral_commission_clinic_type',
            ]);
        });
    }
};
