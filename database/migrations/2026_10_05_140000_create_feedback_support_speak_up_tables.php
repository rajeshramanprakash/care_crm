<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('customer_contact_no', 20)->index();
            $table->string('customer_type', 40)->nullable();
            $table->unsignedBigInteger('customer_ref_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->unsignedBigInteger('operation_lead_id')->nullable()->index();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('service_quality')->nullable();
            $table->unsignedTinyInteger('staff_behaviour')->nullable();
            $table->unsignedTinyInteger('response_time')->nullable();
            $table->unsignedTinyInteger('overall_experience')->nullable();
            $table->text('suggestions')->nullable();
            $table->text('complaint')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 30)->unique();
            $table->string('customer_contact_no', 20)->index();
            $table->string('customer_type', 40)->nullable();
            $table->unsignedBigInteger('customer_ref_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('category', 40)->index();
            $table->string('subject');
            $table->string('status', 30)->default('open')->index();
            $table->string('priority', 10)->default('normal');
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->string('last_reply_by', 20)->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->boolean('unread_for_staff')->default(true);
            $table->boolean('unread_for_customer')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->unsignedBigInteger('sender_user_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });

        Schema::create('speak_up_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->string('follow_up_key_hash');
            $table->string('category', 40)->index();
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_anonymous')->default(true)->index();
            // All submitter columns stay NULL for anonymous submissions.
            $table->string('submitter_type', 20)->nullable();
            $table->unsignedBigInteger('submitter_id')->nullable();
            $table->string('submitter_name')->nullable();
            $table->string('submitter_role', 50)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->boolean('has_unread_reply')->default(false);
            $table->timestamps();
        });

        Schema::create('speak_up_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speak_up_submission_id')->constrained('speak_up_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->string('author_name')->nullable();
            $table->text('message');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        Schema::create('speak_up_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('event', 40)->index();
            $table->boolean('success')->default(true);
            $table->unsignedBigInteger('speak_up_submission_id')->nullable();
            $table->string('details', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('forwarded_for', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('platform', 60)->nullable();
            $table->string('device', 30)->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->text('referrer')->nullable();
            $table->string('accept_language', 255)->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->json('client_info')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('speak_up_settings', function (Blueprint $table) {
            $table->id();
            $table->string('secret_slug', 64)->unique();
            $table->string('password_hash')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->unsignedBigInteger('password_changed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speak_up_settings');
        Schema::dropIfExists('speak_up_access_logs');
        Schema::dropIfExists('speak_up_replies');
        Schema::dropIfExists('speak_up_submissions');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('customer_feedbacks');
    }
};
