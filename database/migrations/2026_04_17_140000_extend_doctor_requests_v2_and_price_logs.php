<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->json('consultation_modes')->nullable()->after('job_title');
            $table->decimal('online_charges', 12, 2)->nullable()->after('consultation_modes');
            $table->decimal('home_visit_charges', 12, 2)->nullable()->after('online_charges');
            $table->decimal('coverage_radius_km', 10, 2)->nullable()->after('home_visit_charges');
            $table->text('base_location_address')->nullable()->after('coverage_radius_km');
            $table->decimal('base_location_lat', 10, 7)->nullable()->after('base_location_address');
            $table->decimal('base_location_lng', 10, 7)->nullable()->after('base_location_lat');
            $table->decimal('clinic_consultation_charges', 12, 2)->nullable()->after('base_location_lng');
            $table->string('clinic_name')->nullable()->after('clinic_consultation_charges');
            $table->text('clinic_address')->nullable()->after('clinic_name');
            $table->decimal('clinic_lat', 10, 7)->nullable()->after('clinic_address');
            $table->decimal('clinic_lng', 10, 7)->nullable()->after('clinic_lat');
            $table->text('permanent_address')->nullable()->after('clinic_lng');
            $table->text('current_address')->nullable()->after('permanent_address');
            $table->boolean('current_same_as_permanent')->default(false)->after('current_address');
            $table->string('bank_name')->nullable()->after('account_name');
        });

        Schema::create('doctor_request_price_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_request_id')->constrained('doctor_requests')->cascadeOnDelete();
            $table->string('mode', 32);
            $table->decimal('old_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_request_price_logs');

        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_modes',
                'online_charges',
                'home_visit_charges',
                'coverage_radius_km',
                'base_location_address',
                'base_location_lat',
                'base_location_lng',
                'clinic_consultation_charges',
                'clinic_name',
                'clinic_address',
                'clinic_lat',
                'clinic_lng',
                'permanent_address',
                'current_address',
                'current_same_as_permanent',
                'bank_name',
            ]);
        });
    }
};
