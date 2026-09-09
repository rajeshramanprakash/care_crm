<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('operation_lead_status_remarks')) {
            return;
        }

        Schema::create('operation_lead_status_remarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_lead_id');
            $table->text('remark');
            $table->text('original_remark')->nullable();
            $table->boolean('is_ai_polished')->default(false);
            $table->timestamp('ai_generated_at')->nullable();
            $table->string('status_at_remark', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name');
            $table->timestamps();

            $table->index(['operation_lead_id', 'id']);
            $table->foreign('operation_lead_id')->references('id')->on('operation_leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_lead_status_remarks');
    }
};
