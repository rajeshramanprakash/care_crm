<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('operation_leads', 'future_prospect_date')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->date('future_prospect_date')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('operation_leads', 'future_prospect_date')) {
            Schema::table('operation_leads', function (Blueprint $table) {
                $table->dropColumn('future_prospect_date');
            });
        }
    }
};
