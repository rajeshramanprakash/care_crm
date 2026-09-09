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
        if (!Schema::hasColumn('vendors', 'lead_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('lead_id')->nullable()->after('id');
            });
        }
        if (!Schema::hasColumn('vendors', 'agreement_number')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('agreement_number')->nullable()->after('lead_id');
            });
        }
        if (!Schema::hasColumn('job_requests', 'agreement_number')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->string('agreement_number')->nullable()->after('lead_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'lead_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn(['lead_id', 'agreement_number']);
            });
        }
        if (Schema::hasColumn('job_requests', 'agreement_number')) {
            Schema::table('job_requests', function (Blueprint $table) {
                $table->dropColumn('agreement_number');
            });
        }
    }
};
