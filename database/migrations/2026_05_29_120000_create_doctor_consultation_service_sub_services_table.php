<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_consultation_service_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_consultation_service_id');
            $table->foreign('doctor_consultation_service_id', 'dcs_sub_parent_fk')
                ->references('id')
                ->on('doctor_consultation_services')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('icon_path')->nullable();
            $table->unsignedInteger('consultation_duration_minutes')->default(30);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('specialization_options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['doctor_consultation_service_id', 'name'], 'dcs_sub_service_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_consultation_service_sub_services');
    }
};
