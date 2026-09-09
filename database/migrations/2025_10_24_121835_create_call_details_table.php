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
        Schema::create('call_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('executive_id')->nullable();
            $table->string('caller_id_number');
            $table->string('agent_number')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('call_status');
            $table->string('recording_url')->nullable();
            $table->datetime('call_received_datetime');
            $table->string('customer_name')->nullable();
            $table->string('lead_code')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->integer('call_duration')->nullable(); // in seconds
            $table->text('call_notes')->nullable();
            $table->string('call_type')->nullable(); // inbound, outbound
            $table->string('call_source')->nullable(); // tata, manual, etc
            $table->json('raw_data')->nullable(); // store complete raw call data
            $table->timestamps();
            
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('executive_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['call_received_datetime']);
            $table->index(['caller_id_number']);
            $table->index(['call_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_details');
    }
};
