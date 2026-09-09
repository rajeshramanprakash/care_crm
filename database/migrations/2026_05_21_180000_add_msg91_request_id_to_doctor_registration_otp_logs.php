<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctor_registration_otp_logs')) {
            return;
        }

        Schema::table('doctor_registration_otp_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_registration_otp_logs', 'msg91_request_id')) {
                $table->string('msg91_request_id', 64)->nullable()->after('sms_provider');
            }
            if (! Schema::hasColumn('doctor_registration_otp_logs', 'msg91_delivery_status')) {
                $table->string('msg91_delivery_status', 32)->nullable()->after('msg91_request_id');
            }
            if (! Schema::hasColumn('doctor_registration_otp_logs', 'msg91_last_error')) {
                $table->text('msg91_last_error')->nullable()->after('msg91_delivery_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('doctor_registration_otp_logs')) {
            return;
        }

        Schema::table('doctor_registration_otp_logs', function (Blueprint $table) {
            foreach (['msg91_last_error', 'msg91_delivery_status', 'msg91_request_id'] as $col) {
                if (Schema::hasColumn('doctor_registration_otp_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
