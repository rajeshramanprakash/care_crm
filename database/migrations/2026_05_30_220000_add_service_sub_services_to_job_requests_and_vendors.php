<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_requests', 'service_sub_services')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->json('service_sub_services')->nullable()->after('job_title');
            });
        }

        if (! Schema::hasColumn('vendors', 'service_sub_services')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->json('service_sub_services')->nullable()->after('job_title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_requests', 'service_sub_services')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->dropColumn('service_sub_services');
            });
        }

        if (Schema::hasColumn('vendors', 'service_sub_services')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('service_sub_services');
            });
        }
    }
};
