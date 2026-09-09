<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctor_portal_leads')) {
            return;
        }

        // Remove orphan portal rows whose sales lead was already deleted.
        if (Schema::hasTable('leads')) {
            DB::table('doctor_portal_leads')
                ->whereNotNull('lead_id')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('leads')
                        ->whereColumn('leads.id', 'doctor_portal_leads.lead_id');
                })
                ->delete();
        }

        Schema::table('doctor_portal_leads', function (Blueprint $table) {
            if (! $this->indexExists('doctor_portal_leads', 'doctor_portal_leads_lead_idx')) {
                $table->index('lead_id', 'doctor_portal_leads_lead_idx');
            }
        });

        // FK cascade: deleting a sales lead also removes the doctor portal referral row.
        if (! $this->foreignKeyExists('doctor_portal_leads', 'doctor_portal_leads_lead_fk')) {
            Schema::table('doctor_portal_leads', function (Blueprint $table) {
                $table->foreign('lead_id', 'doctor_portal_leads_lead_fk')
                    ->references('id')
                    ->on('leads')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('doctor_portal_leads')) {
            return;
        }

        Schema::table('doctor_portal_leads', function (Blueprint $table) {
            if ($this->foreignKeyExists('doctor_portal_leads', 'doctor_portal_leads_lead_fk')) {
                $table->dropForeign('doctor_portal_leads_lead_fk');
            }
            if ($this->indexExists('doctor_portal_leads', 'doctor_portal_leads_lead_idx')) {
                $table->dropIndex('doctor_portal_leads_lead_idx');
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

    private function foreignKeyExists(string $table, string $fk): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.table_constraints WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ?',
            [$db, $table, $fk, 'FOREIGN KEY']
        );

        return ((int) ($row->c ?? 0)) > 0;
    }
};
