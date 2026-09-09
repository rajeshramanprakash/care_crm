<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_website_bookings', 'customer_address_lat')) {
                $table->decimal('customer_address_lat', 10, 7)->nullable()->after('customer_city');
            }
            if (! Schema::hasColumn('consultation_website_bookings', 'customer_address_lng')) {
                $table->decimal('customer_address_lng', 10, 7)->nullable()->after('customer_address_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('consultation_website_bookings', 'customer_address_lng')) {
                $table->dropColumn('customer_address_lng');
            }
            if (Schema::hasColumn('consultation_website_bookings', 'customer_address_lat')) {
                $table->dropColumn('customer_address_lat');
            }
        });
    }
};
