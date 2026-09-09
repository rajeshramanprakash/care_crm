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
        Schema::create('received_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_invoice_id')->constrained('payment_invoices')->onDelete('cascade');
            $table->foreignId('operation_lead_id')->constrained('operation_leads')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->dateTime('received_date');
            $table->string('utr_number')->nullable();
            $table->string('screenshot')->nullable(); // Payment proof/screenshot
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('received_payments');
    }
};
