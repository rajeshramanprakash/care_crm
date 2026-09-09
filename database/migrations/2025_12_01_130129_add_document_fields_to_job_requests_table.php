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
        Schema::table('job_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('job_requests', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('executive_id');
            }
            if (!Schema::hasColumn('job_requests', 'location')) {
                $table->string('location')->nullable()->after('city');
            }
            if (!Schema::hasColumn('job_requests', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('age');
            }
            if (!Schema::hasColumn('job_requests', 'aadhar_card')) {
                $table->string('aadhar_card')->nullable()->after('mobile');
            }
            if (!Schema::hasColumn('job_requests', 'pan_card')) {
                $table->string('pan_card')->nullable()->after('aadhar_card');
            }
            if (!Schema::hasColumn('job_requests', 'qualification_certificate')) {
                $table->string('qualification_certificate')->nullable()->after('pan_card');
            }
            if (!Schema::hasColumn('job_requests', 'account_name')) {
                $table->string('account_name')->nullable()->after('qualification_certificate');
            }
            if (!Schema::hasColumn('job_requests', 'account_number')) {
                $table->string('account_number')->nullable()->after('account_name');
            }
            if (!Schema::hasColumn('job_requests', 'ifsc_code')) {
                $table->string('ifsc_code')->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('job_requests', 'upi_id')) {
                $table->string('upi_id')->nullable()->after('ifsc_code');
            }
            if (!Schema::hasColumn('job_requests', 'bank_document')) {
                $table->string('bank_document')->nullable()->after('upi_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_requests', function (Blueprint $table) {
            if (Schema::hasColumn('job_requests', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
            if (Schema::hasColumn('job_requests', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('job_requests', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('job_requests', 'aadhar_card')) {
                $table->dropColumn('aadhar_card');
            }
            if (Schema::hasColumn('job_requests', 'pan_card')) {
                $table->dropColumn('pan_card');
            }
            if (Schema::hasColumn('job_requests', 'qualification_certificate')) {
                $table->dropColumn('qualification_certificate');
            }
            if (Schema::hasColumn('job_requests', 'account_name')) {
                $table->dropColumn('account_name');
            }
            if (Schema::hasColumn('job_requests', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('job_requests', 'ifsc_code')) {
                $table->dropColumn('ifsc_code');
            }
            if (Schema::hasColumn('job_requests', 'upi_id')) {
                $table->dropColumn('upi_id');
            }
            if (Schema::hasColumn('job_requests', 'bank_document')) {
                $table->dropColumn('bank_document');
            }
        });
    }
};
