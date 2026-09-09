<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_doctor_consultation_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('doctor_consultation_service_id');
            $table->unsignedBigInteger('doctor_consultation_service_sub_service_id')->nullable();
            $table->string('consultation_mode', 32);
            $table->decimal('website_price', 10, 2)->nullable();
            $table->decimal('doctor_max_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('location_id', 'loc_doc_price_loc_fk')
                ->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('doctor_consultation_service_id', 'loc_doc_price_svc_fk')
                ->references('id')->on('doctor_consultation_services')->cascadeOnDelete();
            $table->foreign('doctor_consultation_service_sub_service_id', 'loc_doc_price_sub_fk')
                ->references('id')->on('doctor_consultation_service_sub_services')->nullOnDelete();

            $table->unique(
                ['location_id', 'doctor_consultation_service_id', 'doctor_consultation_service_sub_service_id', 'consultation_mode'],
                'loc_doc_price_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_doctor_consultation_prices');
    }
};
