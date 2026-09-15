<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_service_prices', function (Blueprint $table) {
            $table->string('original_price_12hr')->nullable()->after('price_12hr');
            $table->string('original_price_24hr')->nullable()->after('price_24hr');
            $table->string('original_price_onetime')->nullable()->after('price_onetime');
        });

        Schema::table('job_request_service_prices', function (Blueprint $table) {
            $table->string('original_price_12hr')->nullable()->after('price_12hr');
            $table->string('original_price_24hr')->nullable()->after('price_24hr');
            $table->string('original_price_onetime')->nullable()->after('price_onetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_service_prices', function (Blueprint $table) {
            $table->dropColumn(['original_price_12hr', 'original_price_24hr', 'original_price_onetime']);
        });

        Schema::table('job_request_service_prices', function (Blueprint $table) {
            $table->dropColumn(['original_price_12hr', 'original_price_24hr', 'original_price_onetime']);
        });
    }
};
