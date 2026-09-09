<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_website_profile_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_request_id');
            $table->string('reviewer_display_name', 255);
            $table->string('reviewer_photo_path', 500)->nullable();
            $table->decimal('rating', 3, 1);
            $table->text('body');
            $table->string('consultation_mode', 32);
            $table->date('reviewed_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['doctor_request_id', 'sort_order'], 'dwp_rev_dr_sort_idx');
            $table->foreign('doctor_request_id')
                ->references('id')
                ->on('doctor_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_website_profile_reviews');
    }
};
