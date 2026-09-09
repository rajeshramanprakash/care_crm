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
        Schema::dropIfExists('operation_leads_payment_details');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the table if needed for rollback
        Schema::create('operation_leads_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_lead_id')->constrained('operation_leads')->onDelete('cascade');
            $table->decimal('payment_received', 10, 2)->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('utr_number')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->dateTime('from_date_time')->nullable();
            $table->dateTime('to_date_time')->nullable();
            $table->string('screenshot')->nullable();
            $table->decimal('outstanding_payment', 10, 2)->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }
};
