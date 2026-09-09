<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('operation_lead_id')->nullable()->after('b2b_user_id');
            $table->index('operation_lead_id', 'b2b_leads_operation_lead_idx');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_leads', function (Blueprint $table) {
            $table->dropIndex('b2b_leads_operation_lead_idx');
            $table->dropColumn('operation_lead_id');
        });
    }
};

