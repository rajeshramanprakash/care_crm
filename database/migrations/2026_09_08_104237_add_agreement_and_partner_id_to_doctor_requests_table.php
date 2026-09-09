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
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->string('agreement_number')->nullable()->unique();
            $table->string('partner_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn(['agreement_number', 'partner_id']);
        });
    }
};
