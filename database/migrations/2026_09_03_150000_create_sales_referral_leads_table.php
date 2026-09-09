<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_referral_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generated_by_user_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('assigned_executive_id')->nullable();
            $table->string('mobile', 20);
            $table->string('service')->nullable();
            $table->text('detail')->nullable();
            $table->unsignedInteger('bulk_qty');
            $table->timestamps();

            $table->foreign('generated_by_user_id', 'sales_referral_leads_generated_by_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('lead_id', 'sales_referral_leads_lead_fk')
                ->references('id')
                ->on('leads')
                ->cascadeOnDelete();

            $table->index(['generated_by_user_id', 'created_at'], 'sales_referral_leads_gen_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_referral_leads');
    }
};
