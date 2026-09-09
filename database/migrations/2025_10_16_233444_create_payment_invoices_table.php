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
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_lead_id')->constrained('operation_leads')->onDelete('cascade');
            $table->string('invoice_id')->unique(); // Auto-generated invoice number
            $table->dateTime('from_date');
            $table->dateTime('to_date');
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->boolean('is_received')->default(false);
            $table->integer('work_days')->default(0); // Number of days in this invoice period
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};
