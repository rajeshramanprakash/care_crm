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
        Schema::table('operation_leads', function (Blueprint $table) {
            $table->text('active_deployment_recording_url')->nullable()->after('recording_url')
                ->comment('Recordings from freelancers/vendors during active deployment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_leads', function (Blueprint $table) {
            $table->dropColumn('active_deployment_recording_url');
        });
    }
};
