<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_pricing_rule_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_pricing_rule_id')->constrained('bulk_pricing_rules')->cascadeOnDelete();
            $table->string('target_type', 40)->comment('location_service, location_doctor_price, vendor_price, freelancer_price, doctor_pricing_json, doctor_column');
            $table->unsignedBigInteger('target_id')->nullable()->comment('location / vendor / job_request / doctor_request id');
            $table->json('target_key');
            $table->string('field', 60);
            $table->string('label')->nullable();
            $table->decimal('old_value', 10, 2)->nullable();
            $table->decimal('new_value', 10, 2)->nullable();
            $table->dateTime('reverted_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_pricing_rule_changes');
    }
};
