<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->unsignedBigInteger('referral_user_id')->nullable()->after('mobile');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('referral_user_id');

            $table->text('bank_account_details')->nullable()->after('service_requirement');
            $table->string('account_holder_name')->nullable()->after('bank_account_details');
            $table->string('bank_name')->nullable()->after('account_holder_name');
            $table->string('ifsc_code', 32)->nullable()->after('bank_name');
            $table->string('account_number', 64)->nullable()->after('ifsc_code');

            $table->string('company_registration_file')->nullable()->after('account_number');
            $table->string('company_gst_file')->nullable()->after('company_registration_file');
            $table->string('mou_file')->nullable()->after('company_gst_file');

            $table->foreign('referral_user_id', 'b2b_users_ref_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('b2b_users', function (Blueprint $table) {
            $table->dropForeign('b2b_users_ref_user_fk');
            $table->dropColumn([
                'company_name',
                'referral_user_id',
                'commission_percent',
                'bank_account_details',
                'account_holder_name',
                'bank_name',
                'ifsc_code',
                'account_number',
                'company_registration_file',
                'company_gst_file',
                'mou_file',
            ]);
        });
    }
};

