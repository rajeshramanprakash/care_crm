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
        // Add recording_url to operation_leads table
        if (!Schema::hasColumn('operation_leads', 'recording_url')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->text('recording_url')->nullable()->after('last_call_status');
            });
        }

        // Add recording_url to job_requests table
        if (!Schema::hasColumn('job_requests', 'recording_url')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->text('recording_url')->nullable();
            });
        }

        // Add last_call_status to job_requests table if not exists
        if (!Schema::hasColumn('job_requests', 'last_call_status')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->string('last_call_status')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove recording_url from operation_leads table
        if (Schema::hasColumn('operation_leads', 'recording_url')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->dropColumn('recording_url');
            });
        }

        // Remove recording_url from job_requests table
        if (Schema::hasColumn('job_requests', 'recording_url')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->dropColumn('recording_url');
            });
        }

        // Remove last_call_status from job_requests table
        if (Schema::hasColumn('job_requests', 'last_call_status')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->dropColumn('last_call_status');
            });
        }
    }
};
