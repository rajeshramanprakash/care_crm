<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'state')) {
                $table->string('state', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('locations', 'tier')) {
                $table->string('tier', 50)->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'tier')) {
                $table->dropColumn('tier');
            }
            if (Schema::hasColumn('locations', 'state')) {
                $table->dropColumn('state');
            }
        });
    }
};
