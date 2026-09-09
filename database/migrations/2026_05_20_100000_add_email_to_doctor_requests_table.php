<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_requests', 'email')) {
                $table->string('email', 255)->nullable()->after('contact_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_requests', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
