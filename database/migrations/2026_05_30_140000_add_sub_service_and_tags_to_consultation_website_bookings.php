<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_consultation_service_sub_service_id')->nullable()->after('doctor_consultation_service_id');
            $table->string('sub_service_name', 255)->nullable()->after('doctor_consultation_service_sub_service_id');
            $table->json('selected_tags')->nullable()->after('sub_service_name');

            $table->foreign('doctor_consultation_service_sub_service_id', 'cwb_sub_svc_fk')
                ->references('id')
                ->on('doctor_consultation_service_sub_services')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->dropForeign('cwb_sub_svc_fk');
            $table->dropColumn([
                'doctor_consultation_service_sub_service_id',
                'sub_service_name',
                'selected_tags',
            ]);
        });
    }
};
