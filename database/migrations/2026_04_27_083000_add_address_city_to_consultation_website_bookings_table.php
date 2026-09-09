<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->string('customer_address')->nullable()->after('contact_no');
            $table->string('customer_city')->nullable()->after('customer_address');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->dropColumn(['customer_address', 'customer_city']);
        });
    }
};

