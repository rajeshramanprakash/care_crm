<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('deployment_id')->nullable()->after('operation_lead_id');
            $table->string('staff_name')->nullable()->after('deployment_id');
            $table->unsignedBigInteger('vendor_id')->nullable()->after('staff_name');
            $table->unsignedBigInteger('freelancer_id')->nullable()->after('vendor_id');
            $table->unsignedBigInteger('operation_user_id')->nullable()->index()->after('freelancer_id');
            $table->unsignedBigInteger('operation_manager_id')->nullable()->index()->after('operation_user_id');
            $table->timestamp('operation_seen_at')->nullable()->after('operation_manager_id');
            $table->timestamp('manager_seen_at')->nullable()->after('operation_seen_at');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('type', 20)->default('support')->index()->after('customer_name');
            $table->string('desk_ticket_id', 64)->nullable()->after('closed_at');
            $table->string('desk_reference', 64)->nullable()->after('desk_ticket_id');
            $table->string('desk_status', 40)->nullable()->after('desk_reference');
            $table->string('desk_error', 500)->nullable()->after('desk_status');
            $table->timestamp('desk_sent_at')->nullable()->after('desk_error');
            $table->timestamp('desk_synced_at')->nullable()->after('desk_sent_at');
        });

        Schema::table('support_ticket_messages', function (Blueprint $table) {
            $table->string('desk_reply_id', 64)->nullable()->index()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('support_ticket_messages', function (Blueprint $table) {
            $table->dropColumn('desk_reply_id');
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['type', 'desk_ticket_id', 'desk_reference', 'desk_status', 'desk_error', 'desk_sent_at', 'desk_synced_at']);
        });
        Schema::table('customer_feedbacks', function (Blueprint $table) {
            $table->dropColumn(['deployment_id', 'staff_name', 'vendor_id', 'freelancer_id', 'operation_user_id', 'operation_manager_id', 'operation_seen_at', 'manager_seen_at']);
        });
    }
};
