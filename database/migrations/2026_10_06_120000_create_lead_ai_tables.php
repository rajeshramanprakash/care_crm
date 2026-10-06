<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lead_ai_analyses')) {
            Schema::create('lead_ai_analyses', function (Blueprint $table) {
                $table->id();
                $table->string('lead_type', 20);
                $table->unsignedBigInteger('lead_id');
                $table->string('status', 20)->default('pending');
                $table->string('health', 20)->nullable();
                $table->unsignedTinyInteger('score')->nullable();
                $table->json('result')->nullable();
                $table->json('sources')->nullable();
                $table->string('input_hash', 64)->nullable();
                $table->string('model', 60)->nullable();
                $table->string('error', 1000)->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('analyzed_at')->nullable();
                $table->timestamps();

                $table->unique(['lead_type', 'lead_id']);
                $table->index(['lead_type', 'health']);
            });
        }

        if (! Schema::hasTable('lead_ai_call_notes')) {
            Schema::create('lead_ai_call_notes', function (Blueprint $table) {
                $table->id();
                $table->string('recording_hash', 64)->unique();
                $table->string('lead_type', 20)->nullable();
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->dateTime('call_at')->nullable();
                $table->string('call_status', 30)->nullable();
                $table->string('agent_name', 120)->nullable();
                $table->unsignedInteger('duration')->nullable();
                $table->string('status', 20)->default('pending');
                $table->json('summary')->nullable();
                $table->mediumText('transcript')->nullable();
                $table->string('error', 500)->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamps();

                $table->index(['lead_type', 'lead_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_ai_call_notes');
        Schema::dropIfExists('lead_ai_analyses');
    }
};
