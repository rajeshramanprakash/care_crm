<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. When stage is "inactive", admin/manager can see this remark on lead view.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->text('inactive_stage_remark')->nullable()->after('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('inactive_stage_remark');
        });
    }
};
