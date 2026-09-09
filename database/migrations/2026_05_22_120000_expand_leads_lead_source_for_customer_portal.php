<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'lead_source')) {
            return;
        }

        // Was ENUM(web,ivrs,whatsapp,manual) — customer portal needs crm / app.
        DB::statement('ALTER TABLE `leads` MODIFY `lead_source` VARCHAR(32) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'lead_source')) {
            return;
        }

        DB::statement("ALTER TABLE `leads` MODIFY `lead_source` ENUM('web','ivrs','whatsapp','manual') NULL DEFAULT NULL");
    }
};
