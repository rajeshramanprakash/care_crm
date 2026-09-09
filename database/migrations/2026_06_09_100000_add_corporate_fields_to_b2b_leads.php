<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('b2b_leads', 'detail')) {
                $table->text('detail')->nullable()->after('service_requirement');
            }
            if (! Schema::hasColumn('b2b_leads', 'bulk_qty')) {
                $table->unsignedInteger('bulk_qty')->nullable()->after('detail');
            }
        });

        if (Schema::hasColumn('b2b_leads', 'name')) {
            Schema::table('b2b_leads', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
            });
        }
        if (Schema::hasColumn('b2b_leads', 'mobile')) {
            Schema::table('b2b_leads', function (Blueprint $table) {
                $table->string('mobile', 20)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('b2b_leads', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_leads', 'bulk_qty')) {
                $table->dropColumn('bulk_qty');
            }
            if (Schema::hasColumn('b2b_leads', 'detail')) {
                $table->dropColumn('detail');
            }
        });
    }
};
