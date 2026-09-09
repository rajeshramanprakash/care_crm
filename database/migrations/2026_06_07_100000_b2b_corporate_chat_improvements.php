<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_users') && ! Schema::hasColumn('b2b_users', 'chat_group_name')) {
            Schema::table('b2b_users', function (Blueprint $table) {
                $table->string('chat_group_name', 120)->nullable()->after('chat_enabled');
            });
        }

        if (Schema::hasTable('b2b_chat_messages') && ! Schema::hasColumn('b2b_chat_messages', 'is_read')) {
            Schema::table('b2b_chat_messages', function (Blueprint $table) {
                $table->boolean('is_read')->default(false)->after('body');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_chat_messages') && Schema::hasColumn('b2b_chat_messages', 'is_read')) {
            Schema::table('b2b_chat_messages', function (Blueprint $table) {
                $table->dropColumn('is_read');
            });
        }

        if (Schema::hasTable('b2b_users') && Schema::hasColumn('b2b_users', 'chat_group_name')) {
            Schema::table('b2b_users', function (Blueprint $table) {
                $table->dropColumn('chat_group_name');
            });
        }
    }
};
