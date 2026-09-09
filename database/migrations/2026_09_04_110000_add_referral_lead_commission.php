<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'referral_lead_commission_percent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('referral_lead_commission_percent', 5, 2)
                    ->nullable()
                    ->default(10)
                    ->after('is_next');
            });
        }

        if (Schema::hasTable('sales_referral_leads') && ! Schema::hasColumn('sales_referral_leads', 'commission_percent')) {
            Schema::table('sales_referral_leads', function (Blueprint $table) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('bulk_qty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'referral_lead_commission_percent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referral_lead_commission_percent');
            });
        }

        if (Schema::hasTable('sales_referral_leads') && Schema::hasColumn('sales_referral_leads', 'commission_percent')) {
            Schema::table('sales_referral_leads', function (Blueprint $table) {
                $table->dropColumn('commission_percent');
            });
        }
    }
};
