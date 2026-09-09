<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            if (!Schema::hasColumn('operation_deployment_details', 'payment_verified_at')) {
                $table->datetime('payment_verified_at')->nullable()->after('verify_payment');
            }
        });

        // Backfill so existing debit rows keep their date (updated_at) and don't change when absent is saved
        DB::table('operation_deployment_details')
            ->where('verify_payment', true)
            ->whereNull('payment_verified_at')
            ->update([
                'payment_verified_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('operation_deployment_details', function (Blueprint $table) {
            if (Schema::hasColumn('operation_deployment_details', 'payment_verified_at')) {
                $table->dropColumn('payment_verified_at');
            }
        });
    }
};
