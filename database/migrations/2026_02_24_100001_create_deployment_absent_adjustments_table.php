<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deployment_absent_adjustments')) {
            Schema::create('deployment_absent_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('operation_deployment_detail_id');
                $table->datetime('adjusted_at');
                $table->decimal('amount_deducted', 12, 2)->default(0);
                $table->json('absent_dates')->nullable()->comment('Dates marked absent in this save');
                $table->timestamps();

                $table->foreign('operation_deployment_detail_id', 'depl_absent_adj_detail_id_foreign')
                    ->references('id')
                    ->on('operation_deployment_details')
                    ->onDelete('cascade');
            });
        } else {
            if (!Schema::hasColumn('deployment_absent_adjustments', 'absent_dates')) {
                Schema::table('deployment_absent_adjustments', function (Blueprint $table) {
                    $table->json('absent_dates')->nullable()->after('amount_deducted');
                });
            }
            try {
                Schema::table('deployment_absent_adjustments', function (Blueprint $table) {
                    $table->foreign('operation_deployment_detail_id', 'depl_absent_adj_detail_id_foreign')
                        ->references('id')
                        ->on('operation_deployment_details')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
                    throw $e;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_absent_adjustments');
    }
};
