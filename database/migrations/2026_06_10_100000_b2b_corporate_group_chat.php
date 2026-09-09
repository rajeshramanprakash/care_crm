<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_chat_messages')) {
            Schema::table('b2b_chat_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('b2b_chat_messages', 'thread_type')) {
                    $table->string('thread_type', 16)->default('direct')->after('b2b_user_id');
                }
                if (! Schema::hasColumn('b2b_chat_messages', 'sender_staff_user_id')) {
                    $table->unsignedBigInteger('sender_staff_user_id')->nullable()->after('sender_type');
                }
            });

            if (Schema::hasColumn('b2b_chat_messages', 'user_id')) {
                try {
                    Schema::table('b2b_chat_messages', function (Blueprint $table) {
                        $table->dropForeign(['user_id']);
                    });
                } catch (\Throwable $e) {
                    // FK may not exist on all environments.
                }
                Schema::table('b2b_chat_messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                });
            }

            Schema::table('b2b_chat_messages', function (Blueprint $table) {
                if (! $this->indexExists('b2b_chat_messages', 'b2b_chat_group_thread_idx')) {
                    $table->index(['b2b_user_id', 'thread_type', 'id'], 'b2b_chat_group_thread_idx');
                }
            });
        }

        if (! Schema::hasTable('b2b_chat_read_cursors')) {
            Schema::create('b2b_chat_read_cursors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('b2b_user_id');
                $table->string('reader_type', 16);
                $table->unsignedBigInteger('staff_user_id')->nullable();
                $table->unsignedBigInteger('last_read_message_id')->nullable();
                $table->timestamps();

                $table->unique(['b2b_user_id', 'reader_type', 'staff_user_id'], 'b2b_chat_read_cursor_unique');
                $table->foreign('b2b_user_id')->references('id')->on('b2b_users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_chat_read_cursors');

        if (! Schema::hasTable('b2b_chat_messages')) {
            return;
        }

        Schema::table('b2b_chat_messages', function (Blueprint $table) {
            if ($this->indexExists('b2b_chat_messages', 'b2b_chat_group_thread_idx')) {
                $table->dropIndex('b2b_chat_group_thread_idx');
            }
            if (Schema::hasColumn('b2b_chat_messages', 'sender_staff_user_id')) {
                $table->dropColumn('sender_staff_user_id');
            }
            if (Schema::hasColumn('b2b_chat_messages', 'thread_type')) {
                $table->dropColumn('thread_type');
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$index]);

        return count($rows) > 0;
    }
};
