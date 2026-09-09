<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->string('online_meeting_provider', 32)->nullable()->after('appointment_end_time');
            $table->string('online_meeting_link', 1200)->nullable()->after('online_meeting_provider');
            $table->dateTime('online_meeting_starts_at')->nullable()->after('online_meeting_link');
            $table->dateTime('online_meeting_ends_at')->nullable()->after('online_meeting_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_website_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'online_meeting_provider',
                'online_meeting_link',
                'online_meeting_starts_at',
                'online_meeting_ends_at',
            ]);
        });
    }
};

