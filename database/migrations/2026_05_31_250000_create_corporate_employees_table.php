<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_user_id')->constrained('corporate_users')->cascadeOnDelete();
            $table->string('employee_id', 100);
            $table->string('employee_name');
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('relationship', 100)->nullable();
            $table->date('issuance_date')->nullable();
            $table->date('last_working_date')->nullable();
            $table->string('si_limit', 50)->nullable();
            $table->date('active_from')->nullable();
            $table->date('active_to')->nullable();
            $table->string('room_limit', 50)->nullable();
            $table->string('policy_terms_file')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['corporate_user_id', 'employee_id']);
            $table->index('corporate_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_employees');
    }
};
