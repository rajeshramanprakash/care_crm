<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctor_requests') && ! Schema::hasColumn('doctor_requests', 'portal_lead_commission_percent')) {
            Schema::table('doctor_requests', function (Blueprint $table) {
                $table->decimal('portal_lead_commission_percent', 5, 2)
                    ->default(10)
                    ->after('referral_commission_clinic_type');
            });
        }

        if (Schema::hasTable('doctor_portal_leads') && ! Schema::hasColumn('doctor_portal_leads', 'commission_percent')) {
            Schema::table('doctor_portal_leads', function (Blueprint $table) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('bulk_qty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('doctor_requests') && Schema::hasColumn('doctor_requests', 'portal_lead_commission_percent')) {
            Schema::table('doctor_requests', function (Blueprint $table) {
                $table->dropColumn('portal_lead_commission_percent');
            });
        }

        if (Schema::hasTable('doctor_portal_leads') && Schema::hasColumn('doctor_portal_leads', 'commission_percent')) {
            Schema::table('doctor_portal_leads', function (Blueprint $table) {
                $table->dropColumn('commission_percent');
            });
        }
    }
};
