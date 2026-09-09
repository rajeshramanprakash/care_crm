<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->string('website_card_rating', 32)->nullable();
            $table->string('website_card_attend', 191)->nullable();
            $table->string('website_card_qualification', 255)->nullable();
            $table->text('website_card_experience')->nullable();
            $table->string('website_card_book_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'website_card_rating',
                'website_card_attend',
                'website_card_qualification',
                'website_card_experience',
                'website_card_book_url',
            ]);
        });
    }
};
