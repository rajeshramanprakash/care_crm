<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['insurer_users', 'broker_users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'mou_file')) {
                    $table->string('mou_file')->nullable()->after('password');
                }
                if (! Schema::hasColumn($tableName, 'company_documents')) {
                    $table->json('company_documents')->nullable()->after('mou_file');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['insurer_users', 'broker_users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'company_documents')) {
                    $table->dropColumn('company_documents');
                }
                if (Schema::hasColumn($tableName, 'mou_file')) {
                    $table->dropColumn('mou_file');
                }
            });
        }
    }
};
