<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_requests', 'website_card_modal_description')) {
                $table->text('website_card_modal_description')->nullable()->after('website_card_experience');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_requests', 'website_card_modal_description')) {
                $table->dropColumn('website_card_modal_description');
            }
        });
    }
};
