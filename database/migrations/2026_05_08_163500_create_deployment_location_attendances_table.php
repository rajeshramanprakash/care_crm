<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('deployment_location_attendances')) {
            return;
        }

        Schema::create('deployment_location_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_lead_id')->constrained('operation_leads')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('freelancer_id')->nullable()->constrained('job_requests')->nullOnDelete();

            $table->decimal('customer_latitude', 10, 7)->nullable();
            $table->decimal('customer_longitude', 10, 7)->nullable();
            $table->timestamp('customer_location_captured_at')->nullable();

            $table->decimal('vendor_latitude', 10, 7)->nullable();
            $table->decimal('vendor_longitude', 10, 7)->nullable();
            $table->timestamp('vendor_location_captured_at')->nullable();

            $table->decimal('freelancer_latitude', 10, 7)->nullable();
            $table->decimal('freelancer_longitude', 10, 7)->nullable();
            $table->timestamp('freelancer_location_captured_at')->nullable();

            $table->timestamp('attendance_marked_at')->nullable();
            $table->string('attendance_status')->default('pending');
            $table->timestamps();

            $table->index(['operation_lead_id', 'vendor_id'], 'dla_op_vendor_idx');
            $table->index(['operation_lead_id', 'freelancer_id'], 'dla_op_free_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_location_attendances');
    }
};
