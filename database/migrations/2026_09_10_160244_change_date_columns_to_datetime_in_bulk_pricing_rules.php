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
        Schema::table('bulk_pricing_rules', function (Blueprint $table) {
            $table->dateTime('apply_from_date')->nullable()->change();
            $table->dateTime('apply_to_date')->nullable()->change();
            $table->dateTime('time_period_start_date')->nullable()->change();
            $table->dateTime('time_period_end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_pricing_rules', function (Blueprint $table) {
            $table->date('apply_from_date')->nullable()->change();
            $table->date('apply_to_date')->nullable()->change();
            $table->date('time_period_start_date')->nullable()->change();
            $table->date('time_period_end_date')->nullable()->change();
        });
    }
};
