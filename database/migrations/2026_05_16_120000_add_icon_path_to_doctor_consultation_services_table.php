<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_consultation_services', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_consultation_services', 'icon_path')) {
                $table->string('icon_path', 500)->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_consultation_services', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_consultation_services', 'icon_path')) {
                $table->dropColumn('icon_path');
            }
        });
    }
};
