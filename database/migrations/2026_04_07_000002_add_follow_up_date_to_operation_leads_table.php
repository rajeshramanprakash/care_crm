<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('operation_leads', 'follow_up_date')) {
                $table->dateTime('follow_up_date')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operation_leads', function (Blueprint $table) {
            if (Schema::hasColumn('operation_leads', 'follow_up_date')) {
                $table->dropColumn('follow_up_date');
            }
        });
    }
};
