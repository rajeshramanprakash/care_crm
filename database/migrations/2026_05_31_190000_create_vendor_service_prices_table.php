<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedBigInteger('service_sub_service_id')->default(0);
            $table->decimal('price_12hr', 10, 2)->nullable();
            $table->decimal('price_24hr', 10, 2)->nullable();
            $table->decimal('price_onetime', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(
                ['vendor_id', 'service_id', 'service_sub_service_id'],
                'vendor_svc_prices_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_service_prices');
    }
};
