<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('user_name')->nullable();
            $table->string('action', 30)->index();
            $table->string('module', 100)->nullable()->index();
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->text('url');
            $table->string('description', 500)->nullable();
            $table->json('changes')->nullable();
            $table->json('request_data')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_admin_activity_logs');
    }
};
