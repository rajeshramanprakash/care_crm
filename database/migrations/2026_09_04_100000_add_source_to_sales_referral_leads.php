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

        if (! Schema::hasColumn('sales_referral_leads', 'source')) {
            Schema::table('sales_referral_leads', function (Blueprint $table) {
                $table->string('source', 20)->default('sales')->after('generated_by_user_id');
            });
        }

        DB::table('sales_referral_leads')->whereNull('source')->update(['source' => 'sales']);
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_referral_leads') && Schema::hasColumn('sales_referral_leads', 'source')) {
            Schema::table('sales_referral_leads', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
