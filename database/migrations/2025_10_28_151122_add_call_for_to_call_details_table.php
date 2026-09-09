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
        Schema::table('call_details', function (Blueprint $table) {
            $table->string('call_for')->nullable()->after('lead_id'); // lead, operation_lead, job_request
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_details', function (Blueprint $table) {
            $table->dropColumn('call_for');
        });
    }
};
