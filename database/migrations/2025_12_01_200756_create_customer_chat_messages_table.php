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
        Schema::create('customer_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('sender_type'); // 'vendor', 'freelancer', 'customer'
            $table->unsignedBigInteger('sender_id'); // vendor_id, freelancer_id (job_request id), or customer_id (operation_lead id)
            $table->string('receiver_type'); // 'vendor', 'freelancer', 'customer'
            $table->unsignedBigInteger('receiver_id'); // vendor_id, freelancer_id (job_request id), or customer_id (operation_lead id)
            $table->text('message')->nullable();
            $table->string('attachment')->nullable();
            $table->string('attachment_type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index(['sender_type', 'sender_id'], 'ccm_sender_idx');
            $table->index(['receiver_type', 'receiver_id'], 'ccm_receiver_idx');
            $table->index(['sender_type', 'sender_id', 'receiver_type', 'receiver_id'], 'ccm_chat_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_chat_messages');
    }
};
