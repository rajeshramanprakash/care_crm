<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_leegality_signatures')) {
            return;
        }

        Schema::create('vendor_leegality_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->string('leegality_document_id', 120)->nullable()->index();
            $table->string('irn', 255)->nullable();
            $table->string('signature_status', 40)->default('SENT')->index();
            $table->string('document_status', 40)->nullable();
            $table->string('signer_action', 40)->nullable();
            $table->string('signer_email', 255)->nullable();
            $table->string('signer_name', 255)->nullable();
            $table->text('sign_url')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_document')->nullable();
            $table->string('audit_trail')->nullable();
            $table->json('create_response')->nullable();
            $table->json('last_webhook_payload')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_leegality_signatures');
    }
};
