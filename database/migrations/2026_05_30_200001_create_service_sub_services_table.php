<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->foreign('service_id', 'svc_sub_parent_fk')
                ->references('id')
                ->on('services')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('icon_path', 500)->nullable();
            $table->unsignedInteger('consultation_duration_minutes')->default(30);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('specialization_options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'name'], 'svc_sub_name_unique');
            $table->index(['service_id', 'sort_order'], 'svc_sub_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_sub_services');
    }
};
