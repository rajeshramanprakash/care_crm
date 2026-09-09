<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vendors', 'vendor_services')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->json('vendor_services')->nullable()->after('service_sub_services');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'vendor_services')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('vendor_services');
            });
        }
    }
};
