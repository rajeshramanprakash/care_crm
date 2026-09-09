<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missed_callback_step_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('step_index')->unique();
            $table->string('kind', 32);
            $table->unsignedSmallInteger('delay_minutes')->nullable();
            $table->time('window_start')->nullable();
            $table->time('window_end')->nullable();
            $table->smallInteger('day_offset')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_callback_step_configs');
    }
};
