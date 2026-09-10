<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('pricing_type')->comment('doctor, vendor, freelancer, website');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('sub_service_id')->nullable();
            $table->string('mode_type')->nullable()->comment('online, home_visit, clinic, 12_hours, 24_hours, both, one_time');
            $table->string('change_type')->comment('increase_fixed, increase_percent, decrease_fixed, decrease_percent');
            $table->decimal('value', 10, 2);
            $table->string('apply_to')->comment('new, old, all');
            $table->date('apply_from_date')->nullable();
            $table->date('apply_to_date')->nullable();
            $table->string('city_filter')->default('current')->comment('current, all, tier_1, tier_2, tier_3');
            $table->string('time_period')->comment('permanent, temporary');
            $table->date('time_period_start_date')->nullable();
            $table->date('time_period_end_date')->nullable();
            $table->boolean('status')->default(1)->comment('1: active, 0: inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_pricing_rules');
    }
};
