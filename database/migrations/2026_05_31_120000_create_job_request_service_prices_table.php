<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_request_service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_request_id')->constrained('job_requests')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedBigInteger('service_sub_service_id')->default(0);
            $table->decimal('price_12hr', 10, 2)->nullable();
            $table->decimal('price_24hr', 10, 2)->nullable();
            $table->decimal('price_onetime', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(
                ['job_request_id', 'service_id', 'service_sub_service_id'],
                'jr_svc_prices_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_request_service_prices');
    }
};
