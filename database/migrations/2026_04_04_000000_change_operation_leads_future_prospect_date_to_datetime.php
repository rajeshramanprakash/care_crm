<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('operation_leads', 'future_prospect_date')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->dateTime('future_prospect_date')->nullable();
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `operation_leads` MODIFY `future_prospect_date` DATETIME NULL');
        } else {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->dateTime('future_prospect_date')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('operation_leads', 'future_prospect_date')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `operation_leads` MODIFY `future_prospect_date` DATE NULL');
        } else {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->date('future_prospect_date')->nullable()->change();
            });
        }
    }
};
