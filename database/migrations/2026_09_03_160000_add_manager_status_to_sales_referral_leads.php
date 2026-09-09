<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_referral_leads')) {
            return;
        }

        Schema::table('sales_referral_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_referral_leads', 'status')) {
                $table->string('status', 20)->default('pending')->after('bulk_qty');
            }
            if (! Schema::hasColumn('sales_referral_leads', 'manager_user_id')) {
                $table->unsignedBigInteger('manager_user_id')->nullable()->after('generated_by_user_id');
            }
            if (! Schema::hasColumn('sales_referral_leads', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('assigned_executive_id');
            }
            if (! Schema::hasColumn('sales_referral_leads', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            }
            if (! Schema::hasColumn('sales_referral_leads', 'manager_remark')) {
                $table->string('manager_remark', 500)->nullable()->after('reviewed_at');
            }
        });

        // Existing rows that already created a sales lead are treated as approved.
        DB::table('sales_referral_leads')
            ->whereNotNull('lead_id')
            ->update(['status' => 'approved']);

        if (! $this->indexExists('sales_referral_leads', 'sales_referral_leads_manager_status_idx')) {
            Schema::table('sales_referral_leads', function (Blueprint $table) {
                $table->index(['manager_user_id', 'status'], 'sales_referral_leads_manager_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_referral_leads')) {
            return;
        }

        Schema::table('sales_referral_leads', function (Blueprint $table) {
            if ($this->indexExists('sales_referral_leads', 'sales_referral_leads_manager_status_idx')) {
                $table->dropIndex('sales_referral_leads_manager_status_idx');
            }
            foreach (['manager_remark', 'reviewed_at', 'reviewed_by_user_id', 'manager_user_id', 'status'] as $col) {
                if (Schema::hasColumn('sales_referral_leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }
};
