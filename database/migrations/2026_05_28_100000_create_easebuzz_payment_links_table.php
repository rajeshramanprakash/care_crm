<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('easebuzz_payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('merchant_txn', 64)->unique();
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->text('message')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_url', 2048)->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('easebuzz_create_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('easebuzz_verify_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('created_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('easebuzz_payment_links');
    }
};
