<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_user_id');
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('service_requirement');
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->foreign('b2b_user_id', 'b2b_leads_user_fk')
                ->references('id')
                ->on('b2b_users')
                ->cascadeOnDelete();

            $table->index(['b2b_user_id', 'created_at'], 'b2b_leads_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_leads');
    }
};

