<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('operation_leads', function (Blueprint $table) {
            $table->string('price_issue_remark')->nullable()->after('status_remark');
            $table->string('inactive_remark')->nullable()->after('price_issue_remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_leads', function (Blueprint $table) {
            $table->dropColumn(['price_issue_remark', 'inactive_remark']);
        });
    }
};
