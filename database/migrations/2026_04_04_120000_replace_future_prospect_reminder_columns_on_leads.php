<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('leads', 'future_prospect_reminder_sent_at')) {
                $drops[] = 'future_prospect_reminder_sent_at';
            }
            if (Schema::hasColumn('leads', 'future_prospect_reminder_broadcast_at')) {
                $drops[] = 'future_prospect_reminder_broadcast_at';
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'future_prospect_reminder_at')) {
                $table->timestamp('future_prospect_reminder_at')->nullable()->after('future_prospect_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'future_prospect_reminder_at')) {
                $table->dropColumn('future_prospect_reminder_at');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'future_prospect_reminder_sent_at')) {
                $table->timestamp('future_prospect_reminder_sent_at')->nullable()->after('future_prospect_date');
            }
        });
    }
};
