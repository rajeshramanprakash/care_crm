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
        Schema::table('location_services', function (Blueprint $table) {
            $table->decimal('original_price_12hr', 10, 2)->nullable()->after('price_12hr');
            $table->decimal('original_price_24hr', 10, 2)->nullable()->after('price_24hr');
            $table->decimal('original_price_onetime', 10, 2)->nullable()->after('price_onetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_services', function (Blueprint $table) {
            $table->dropColumn(['original_price_12hr', 'original_price_24hr', 'original_price_onetime']);
        });
    }
};
