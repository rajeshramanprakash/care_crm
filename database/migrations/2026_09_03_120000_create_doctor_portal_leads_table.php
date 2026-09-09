<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_portal_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_request_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('mobile', 20);
            $table->string('service')->nullable();
            $table->text('detail')->nullable();
            $table->unsignedInteger('bulk_qty');
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->foreign('doctor_request_id', 'doctor_portal_leads_doctor_fk')
                ->references('id')
                ->on('doctor_requests')
                ->cascadeOnDelete();

            $table->index(['doctor_request_id', 'created_at'], 'doctor_portal_leads_doctor_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_portal_leads');
    }
};
