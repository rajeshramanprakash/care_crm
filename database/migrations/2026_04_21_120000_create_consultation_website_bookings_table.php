<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_website_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_request_id');
            $table->unsignedBigInteger('doctor_consultation_service_id')->nullable();
            $table->string('customer_name');
            $table->string('contact_no', 32);
            $table->string('consultation_mode', 32);
            $table->date('appointment_date');
            $table->timestamps();

            $table->index(['doctor_request_id', 'created_at']);

            $table->foreign('doctor_request_id', 'cwb_doctor_req_fk')
                ->references('id')
                ->on('doctor_requests')
                ->cascadeOnDelete();
            $table->foreign('doctor_consultation_service_id', 'cwb_dcs_fk')
                ->references('id')
                ->on('doctor_consultation_services')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_website_bookings');
    }
};
