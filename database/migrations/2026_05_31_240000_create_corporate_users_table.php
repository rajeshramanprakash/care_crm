<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_users', function (Blueprint $table) {
            $table->id();
            $table->string('corporate_name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('owner_type', 20); // insurer | broker
            $table->unsignedBigInteger('insurer_user_id')->nullable();
            $table->unsignedBigInteger('broker_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('insurer_user_id')->references('id')->on('insurer_users')->cascadeOnDelete();
            $table->foreign('broker_user_id')->references('id')->on('broker_users')->cascadeOnDelete();
            $table->index(['owner_type', 'insurer_user_id']);
            $table->index(['owner_type', 'broker_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_users');
    }
};
