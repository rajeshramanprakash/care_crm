<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date_time')->nullable();
            $table->string('lead_id')->nullable()->unique();
            $table->string('customer_name');
            $table->string('contact_no', 32);
            $table->string('mobile', 32)->nullable();
            $table->string('name');
            $table->string('profile_image')->nullable();
            $table->string('age')->nullable();
            $table->string('gender', 32)->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->string('shift', 16)->nullable();
            $table->string('total_experience')->nullable();
            $table->string('job_title')->nullable();
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->string('aadhar_card')->nullable();
            $table->string('pan_card')->nullable();
            $table->string('qualification_certificate')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('bank_document')->nullable();
            $table->string('approval_status', 32)->default('pending');
            $table->text('admin_remark')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_requests');
    }
};
