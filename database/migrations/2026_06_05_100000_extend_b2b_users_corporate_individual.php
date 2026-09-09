<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('b2b_users')) {
            return;
        }

        Schema::table('b2b_users', function (Blueprint $table) {
            if (! Schema::hasColumn('b2b_users', 'account_type')) {
                $table->string('account_type', 32)->default('b2b_corporate')->after('id');
            }
            if (! Schema::hasColumn('b2b_users', 'bulk_requirement_qty')) {
                $table->unsignedInteger('bulk_requirement_qty')->nullable()->after('service_requirement');
            }
            if (! Schema::hasColumn('b2b_users', 'chat_enabled')) {
                $table->boolean('chat_enabled')->default(false)->after('bulk_requirement_qty');
            }
        });

        if (Schema::hasColumn('b2b_users', 'mobile')) {
            DB::statement('ALTER TABLE b2b_users MODIFY mobile VARCHAR(20) NULL');
        }
        if (Schema::hasColumn('b2b_users', 'service_requirement')) {
            DB::statement('ALTER TABLE b2b_users MODIFY service_requirement VARCHAR(255) NULL');
        }

        if (! Schema::hasTable('b2b_user_chat_peers')) {
            Schema::create('b2b_user_chat_peers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('b2b_user_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['b2b_user_id', 'user_id']);
                $table->foreign('b2b_user_id')->references('id')->on('b2b_users')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('b2b_chat_messages')) {
            Schema::create('b2b_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('b2b_user_id');
                $table->unsignedBigInteger('user_id');
                $table->string('sender_type', 16);
                $table->text('body');
                $table->timestamps();

                $table->index(['b2b_user_id', 'user_id', 'id']);
                $table->foreign('b2b_user_id')->references('id')->on('b2b_users')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_chat_messages');
        Schema::dropIfExists('b2b_user_chat_peers');

        if (Schema::hasTable('b2b_users')) {
            Schema::table('b2b_users', function (Blueprint $table) {
                if (Schema::hasColumn('b2b_users', 'chat_enabled')) {
                    $table->dropColumn('chat_enabled');
                }
                if (Schema::hasColumn('b2b_users', 'bulk_requirement_qty')) {
                    $table->dropColumn('bulk_requirement_qty');
                }
                if (Schema::hasColumn('b2b_users', 'account_type')) {
                    $table->dropColumn('account_type');
                }
            });
        }
    }
};
