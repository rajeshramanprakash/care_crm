<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lead_status_remarks')) {
            return;
        }

        if (! Schema::hasColumn('lead_status_remarks', 'original_remark')) {
            Schema::table('lead_status_remarks', function (Blueprint $table) {
                $table->text('original_remark')->nullable()->after('remark');
            });
        }

        if (! Schema::hasColumn('lead_status_remarks', 'is_ai_polished')) {
            Schema::table('lead_status_remarks', function (Blueprint $table) {
                $table->boolean('is_ai_polished')->default(false)->after('original_remark');
            });
        }

        if (! Schema::hasColumn('lead_status_remarks', 'ai_generated_at')) {
            Schema::table('lead_status_remarks', function (Blueprint $table) {
                $table->timestamp('ai_generated_at')->nullable()->after('is_ai_polished');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lead_status_remarks')) {
            return;
        }

        $columns = [];
        if (Schema::hasColumn('lead_status_remarks', 'ai_generated_at')) {
            $columns[] = 'ai_generated_at';
        }
        if (Schema::hasColumn('lead_status_remarks', 'is_ai_polished')) {
            $columns[] = 'is_ai_polished';
        }
        if (Schema::hasColumn('lead_status_remarks', 'original_remark')) {
            $columns[] = 'original_remark';
        }

        if ($columns !== []) {
            Schema::table('lead_status_remarks', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
