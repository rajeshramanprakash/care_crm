<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_referral_user_id');
            $table->unsignedBigInteger('doctor_request_id');
            $table->unsignedBigInteger('consultation_website_booking_id')->nullable();
            $table->unique('consultation_website_booking_id', 'dr_ref_comm_booking_uq');
            $table->foreign('doctor_referral_user_id', 'dr_ref_comm_ref_user_fk')
                ->references('id')->on('doctor_referral_users')->cascadeOnDelete();
            $table->foreign('doctor_request_id', 'dr_ref_comm_doctor_fk')
                ->references('id')->on('doctor_requests')->cascadeOnDelete();
            $table->foreign('consultation_website_booking_id', 'dr_ref_comm_booking_fk')
                ->references('id')->on('consultation_website_bookings')->nullOnDelete();
            $table->unsignedBigInteger('operation_lead_id')->nullable()->index();
            $table->string('lead_reference', 64)->nullable();
            $table->string('doctor_name', 255);
            $table->string('consultation_service_name', 255)->nullable();
            $table->string('consultation_mode', 32);
            $table->date('service_date')->nullable();
            $table->string('city', 128)->nullable();
            $table->decimal('booking_amount', 10, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['doctor_referral_user_id', 'service_date'], 'dr_ref_comm_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_referral_commissions');
    }
};
