<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_service_price_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_request_id')->constrained('job_requests')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->unsignedBigInteger('service_sub_service_id')->default(0);
            $table->string('sub_service_name')->nullable();
            $table->string('price_type', 16);
            $table->decimal('current_price', 12, 2)->nullable();
            $table->decimal('requested_price', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['job_request_id', 'status'], 'fl_price_chg_jr_st_idx');
            $table->index(['status', 'created_at'], 'fl_price_chg_st_dt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_service_price_change_requests');
    }
};
