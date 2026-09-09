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
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('vendors', 'age')) {
                $table->integer('age')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('vendors', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('age');
            }
            if (!Schema::hasColumn('vendors', 'expected_salary')) {
                $table->decimal('expected_salary', 10, 2)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('vendors', 'total_experience')) {
                $table->string('total_experience')->nullable()->after('expected_salary');
            }
            if (!Schema::hasColumn('vendors', 'job_title')) {
                $table->string('job_title')->nullable()->after('total_experience');
            }
            if (!Schema::hasColumn('vendors', 'location')) {
                $table->string('location')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('vendors', 'aadhar_card')) {
                $table->string('aadhar_card')->nullable()->after('location');
            }
            if (!Schema::hasColumn('vendors', 'pan_card')) {
                $table->string('pan_card')->nullable()->after('aadhar_card');
            }
            if (!Schema::hasColumn('vendors', 'qualification_certificate')) {
                $table->string('qualification_certificate')->nullable()->after('pan_card');
            }
            if (!Schema::hasColumn('vendors', 'bank_document')) {
                $table->string('bank_document')->nullable()->after('upi_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
            if (Schema::hasColumn('vendors', 'age')) {
                $table->dropColumn('age');
            }
            if (Schema::hasColumn('vendors', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('vendors', 'expected_salary')) {
                $table->dropColumn('expected_salary');
            }
            if (Schema::hasColumn('vendors', 'total_experience')) {
                $table->dropColumn('total_experience');
            }
            if (Schema::hasColumn('vendors', 'job_title')) {
                $table->dropColumn('job_title');
            }
            if (Schema::hasColumn('vendors', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('vendors', 'aadhar_card')) {
                $table->dropColumn('aadhar_card');
            }
            if (Schema::hasColumn('vendors', 'pan_card')) {
                $table->dropColumn('pan_card');
            }
            if (Schema::hasColumn('vendors', 'qualification_certificate')) {
                $table->dropColumn('qualification_certificate');
            }
            if (Schema::hasColumn('vendors', 'bank_document')) {
                $table->dropColumn('bank_document');
            }
        });
    }
};
