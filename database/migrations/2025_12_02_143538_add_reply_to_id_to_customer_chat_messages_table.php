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
        Schema::table('customer_chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_chat_messages', 'reply_to_id')) {
                $table->unsignedBigInteger('reply_to_id')->nullable()->after('is_read');
                $table->foreign('reply_to_id')->references('id')->on('customer_chat_messages')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('customer_chat_messages', 'reply_to_id')) {
                $table->dropForeign(['reply_to_id']);
                $table->dropColumn('reply_to_id');
            }
        });
    }
};
