<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctor_registration_otp_logs')) {
            return;
        }

        Schema::create('doctor_registration_otp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('registration_type', 20)->default('doctor')->index();
            $table->string('mobile', 10)->index();
            $table->string('otp', 6);
            $table->timestamp('sent_at')->index();
            $table->string('sent_ip', 45)->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->string('verified_ip', 45)->nullable();
            $table->unsignedSmallInteger('verify_attempts')->default(0);
            $table->timestamp('last_verify_attempt_at')->nullable();
            $table->string('last_verify_attempt_ip', 45)->nullable();
            $table->string('sms_provider', 32)->default('msg91');
            $table->string('msg91_request_id', 64)->nullable();
            $table->string('msg91_delivery_status', 32)->nullable();
            $table->text('msg91_last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_registration_otp_logs');
    }
};
