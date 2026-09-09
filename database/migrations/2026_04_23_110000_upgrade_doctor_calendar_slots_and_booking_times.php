<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_calendar_availability_slots', function (Blueprint $table) {
            $table->string('break_start', 5)->nullable()->after('time_end');
            $table->string('break_end', 5)->nullable()->after('break_start');
            $table->dropUnique('dcal_doc_date_uq');
            $table->unique(['doctor_request_id', 'slot_date', 'time_start', 'time_end'], 'dcal_doc_date_time_uq');
        });

        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->string('appointment_start_time', 5)->nullable()->after('appointment_date');
            $table->string('appointment_end_time', 5)->nullable()->after('appointment_start_time');
            $table->index(['doctor_request_id', 'appointment_date', 'appointment_start_time'], 'cwb_doc_date_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->dropIndex('cwb_doc_date_start_idx');
            $table->dropColumn(['appointment_start_time', 'appointment_end_time']);
        });

        Schema::table('doctor_calendar_availability_slots', function (Blueprint $table) {
            $table->dropUnique('dcal_doc_date_time_uq');
            $table->unique(['doctor_request_id', 'slot_date'], 'dcal_doc_date_uq');
            $table->dropColumn(['break_start', 'break_end']);
        });
    }
};

