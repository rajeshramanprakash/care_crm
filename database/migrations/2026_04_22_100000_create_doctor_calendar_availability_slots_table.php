<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_calendar_availability_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_request_id');
            $table->date('slot_date');
            $table->string('time_start', 5);
            $table->string('time_end', 5);
            $table->timestamps();

            $table->unique(['doctor_request_id', 'slot_date'], 'dcal_doc_date_uq');
            $table->index(['doctor_request_id', 'slot_date'], 'dcal_doc_date_idx');

            $table->foreign('doctor_request_id', 'dcal_dr_fk')
                ->references('id')
                ->on('doctor_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_calendar_availability_slots');
    }
};
