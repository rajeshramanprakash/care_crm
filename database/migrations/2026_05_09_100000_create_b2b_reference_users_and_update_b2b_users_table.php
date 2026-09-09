<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_reference_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('b2b_users', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_users', 'referral_user_id')) {
                $table->dropForeign('b2b_users_ref_user_fk');
                $table->dropColumn('referral_user_id');
            }
            if (! Schema::hasColumn('b2b_users', 'b2b_reference_user_id')) {
                $table->unsignedBigInteger('b2b_reference_user_id')->nullable()->after('mobile');
                $table->foreign('b2b_reference_user_id', 'b2b_users_b2b_ref_fk')
                    ->references('id')
                    ->on('b2b_reference_users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('b2b_users', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_users', 'b2b_reference_user_id')) {
                $table->dropForeign('b2b_users_b2b_ref_fk');
                $table->dropColumn('b2b_reference_user_id');
            }
            $table->unsignedBigInteger('referral_user_id')->nullable()->after('mobile');
            $table->foreign('referral_user_id', 'b2b_users_ref_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::dropIfExists('b2b_reference_users');
    }
};
