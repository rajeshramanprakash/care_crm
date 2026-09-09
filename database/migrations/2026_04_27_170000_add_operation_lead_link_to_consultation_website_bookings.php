<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('operation_lead_id')->nullable()->after('doctor_consultation_service_id');
            $table->index('operation_lead_id', 'cwb_operation_lead_idx');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->dropIndex('cwb_operation_lead_idx');
            $table->dropColumn('operation_lead_id');
        });
    }
};

