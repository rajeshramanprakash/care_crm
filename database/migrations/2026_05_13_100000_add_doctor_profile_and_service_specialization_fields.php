<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_consultation_services', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_consultation_services', 'category')) {
                $table->string('category', 160)->nullable()->after('name');
            }
            if (! Schema::hasColumn('doctor_consultation_services', 'specialization_options')) {
                $table->json('specialization_options')->nullable()->after('category');
            }
        });

        Schema::table('doctor_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_requests', 'fluent_languages')) {
                $table->json('fluent_languages')->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('doctor_requests', 'about_text')) {
                $table->text('about_text')->nullable()->after('fluent_languages');
            }
            if (! Schema::hasColumn('doctor_requests', 'education_history')) {
                $table->json('education_history')->nullable()->after('about_text');
            }
            if (! Schema::hasColumn('doctor_requests', 'experience_history')) {
                $table->json('experience_history')->nullable()->after('education_history');
            }
            if (! Schema::hasColumn('doctor_requests', 'specializations')) {
                $table->json('specializations')->nullable()->after('experience_history');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            foreach (['specializations', 'experience_history', 'education_history', 'about_text', 'fluent_languages'] as $col) {
                if (Schema::hasColumn('doctor_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('doctor_consultation_services', function (Blueprint $table) {
            foreach (['specialization_options', 'category'] as $col) {
                if (Schema::hasColumn('doctor_consultation_services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
