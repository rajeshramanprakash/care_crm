<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table may already exist on some servers — skip instantly to avoid hangs on data backfill.
     */
    public function up(): void
    {
        if (Schema::hasTable('lead_status_remarks')) {
            return;
        }

        Schema::create('lead_status_remarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->text('remark');
            $table->string('status_at_remark', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name');
            $table->timestamps();

            $table->index(['lead_id', 'id']);
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_remarks');
    }
};
