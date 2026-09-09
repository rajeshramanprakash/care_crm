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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('executive_id');
            $table->string('caller_id_number');
            $table->string('agent_number');
            $table->string('agent_name');
            $table->string('call_status');
            $table->string('recording_url')->nullable();
            $table->datetime('call_received_datetime');
            $table->string('customer_name')->nullable();
            $table->string('lead_code')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('executive_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
