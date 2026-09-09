<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->unsignedInteger('consultation_duration_minutes')->nullable()->after('consultation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->dropColumn('consultation_duration_minutes');
        });
    }
};

