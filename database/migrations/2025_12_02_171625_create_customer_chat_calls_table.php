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
        Schema::create('customer_chat_calls', function (Blueprint $table) {
            $table->id();
            $table->string('caller_type'); // customer, vendor, freelancer
            $table->unsignedBigInteger('caller_id');
            $table->string('receiver_type'); // customer, vendor, freelancer
            $table->unsignedBigInteger('receiver_id');
            $table->enum('call_status', ['initiated', 'connected', 'ended', 'missed', 'rejected'])->default('initiated');
            $table->timestamp('call_started_at')->nullable();
            $table->timestamp('call_ended_at')->nullable();
            $table->integer('call_duration')->nullable(); // in seconds
            $table->timestamps();

            // Add indexes
            $table->index(['caller_type', 'caller_id'], 'customer_chat_calls_caller_idx');
            $table->index(['receiver_type', 'receiver_id'], 'customer_chat_calls_receiver_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_chat_calls');
    }
};
