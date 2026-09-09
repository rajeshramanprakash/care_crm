<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_referral_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile', 20)->unique();
            $table->decimal('commission_percent', 6, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('doctor_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_requests', 'doctor_referral_user_id')) {
                $table->unsignedBigInteger('doctor_referral_user_id')->nullable();
                $table->foreign('doctor_referral_user_id', 'doctor_requests_doctor_ref_user_fk')
                    ->references('id')
                    ->on('doctor_referral_users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_requests', 'doctor_referral_user_id')) {
                $table->dropForeign('doctor_requests_doctor_ref_user_fk');
                $table->dropColumn('doctor_referral_user_id');
            }
        });

        Schema::dropIfExists('doctor_referral_users');
    }
};
