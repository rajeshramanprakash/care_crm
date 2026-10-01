<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_pricing_rules', function (Blueprint $table) {
            $table->string('state', 20)->default('active')->after('status')
                ->comment('scheduled, active, completed, reverted, failed');
            $table->unsignedBigInteger('created_by')->nullable()->after('state');
            $table->string('created_by_name')->nullable()->after('created_by');
            $table->dateTime('applied_at')->nullable()->after('created_by_name');
            $table->dateTime('reverted_at')->nullable()->after('applied_at');
            $table->unsignedInteger('affected_count')->default(0)->after('reverted_at');
            $table->text('error_message')->nullable()->after('affected_count');
        });

        DB::table('bulk_pricing_rules')->where('status', 0)->update(['state' => 'reverted']);
        DB::table('bulk_pricing_rules')->where('status', 1)->update(['applied_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('bulk_pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['state', 'created_by', 'created_by_name', 'applied_at', 'reverted_at', 'affected_count', 'error_message']);
        });
    }
};
