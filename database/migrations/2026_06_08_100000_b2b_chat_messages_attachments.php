<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('b2b_chat_messages')) {
            return;
        }

        Schema::table('b2b_chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('b2b_chat_messages', 'attachment')) {
                $table->string('attachment')->nullable()->after('body');
            }
            if (! Schema::hasColumn('b2b_chat_messages', 'attachment_type')) {
                $table->string('attachment_type', 128)->nullable()->after('attachment');
            }
        });

        if (Schema::hasColumn('b2b_chat_messages', 'body')) {
            DB::statement('ALTER TABLE b2b_chat_messages MODIFY body TEXT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('b2b_chat_messages')) {
            return;
        }

        Schema::table('b2b_chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_chat_messages', 'attachment_type')) {
                $table->dropColumn('attachment_type');
            }
            if (Schema::hasColumn('b2b_chat_messages', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
};
