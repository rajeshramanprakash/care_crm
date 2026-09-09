<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->decimal('referral_commission_online', 10, 2)->nullable()->after('doctor_referral_user_id');
            $table->decimal('referral_commission_home_visit', 10, 2)->nullable()->after('referral_commission_online');
            $table->decimal('referral_commission_clinic', 10, 2)->nullable()->after('referral_commission_home_visit');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'referral_commission_online',
                'referral_commission_home_visit',
                'referral_commission_clinic',
            ]);
        });
    }
};
