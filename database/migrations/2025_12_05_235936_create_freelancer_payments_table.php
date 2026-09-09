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
        Schema::create('freelancer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_request_id')->constrained('job_requests')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('bank_transfer'); // bank_transfer, upi, cash, cheque
            $table->string('transaction_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->string('screenshot')->nullable(); // Payment screenshot path
            $table->date('payment_date');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['job_request_id', 'payment_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancer_payments');
    }
};
