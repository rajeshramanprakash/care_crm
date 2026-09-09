<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missed_callback_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('missed_callback_sequence_id')->constrained('missed_callback_sequences')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_index');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('result', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['missed_callback_sequence_id', 'step_index'], 'mcb_attempts_seq_step_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_callback_attempts');
    }
};
