<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_requests', 'consultation_sub_services')) {
                $table->json('consultation_sub_services')->nullable()->after('specializations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_requests', 'consultation_sub_services')) {
                $table->dropColumn('consultation_sub_services');
            }
        });
    }
};
