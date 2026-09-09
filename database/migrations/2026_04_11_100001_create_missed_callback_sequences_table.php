<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missed_callback_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('lead_type', 32);
            $table->unsignedBigInteger('lead_id');
            $table->string('inbound_tata_call_id', 128)->nullable()->index();
            $table->unsignedBigInteger('executive_id');
            $table->string('customer_phone', 32);
            $table->string('status', 32)->default('active')->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('awaiting_disposition_at')->nullable()->index();
            $table->string('stop_reason', 64)->nullable();
            $table->timestamps();

            $table->index(['lead_type', 'lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_callback_sequences');
    }
};
