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
            $table->text('stopped_remark')->nullable()->after('ongoing_stopped');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_leads', function (Blueprint $table) {
            $table->dropColumn('stopped_remark');
        });
    }
};
