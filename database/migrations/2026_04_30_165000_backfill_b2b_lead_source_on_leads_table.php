<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('leads')
            || !Schema::hasTable('b2b_leads')
            || !Schema::hasTable('b2b_users')
            || !Schema::hasColumn('b2b_leads', 'lead_id')
            || !Schema::hasColumn('b2b_users', 'company_name')
        ) {
            return;
        }

        DB::statement("
            UPDATE leads l
            INNER JOIN b2b_leads bl ON bl.lead_id = l.id
            INNER JOIN b2b_users bu ON bu.id = bl.b2b_user_id
            SET l.lead_source = CASE
                WHEN TRIM(COALESCE(bu.company_name, '')) <> '' THEN CONCAT('b2b (', TRIM(bu.company_name), ')')
                WHEN TRIM(COALESCE(bu.name, '')) <> '' THEN CONCAT('b2b (', TRIM(bu.name), ')')
                ELSE 'b2b'
            END
            WHERE bl.lead_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Intentionally left blank: lead_source should remain as historical audit data.
    }
};
