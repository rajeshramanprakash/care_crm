<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_consultation_price_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_request_id');
            $table->foreign('doctor_request_id', 'doc_price_chg_dr_fk')
                ->references('id')->on('doctor_requests')->cascadeOnDelete();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->unsignedBigInteger('sub_service_id')->default(0);
            $table->string('sub_service_name')->nullable();
            $table->string('consultation_mode', 32);
            $table->decimal('current_price', 12, 2)->nullable();
            $table->decimal('requested_price', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['doctor_request_id', 'status'], 'doc_price_chg_dr_st_idx');
            $table->index(['status', 'created_at'], 'doc_price_chg_st_dt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_consultation_price_change_requests');
    }
};
