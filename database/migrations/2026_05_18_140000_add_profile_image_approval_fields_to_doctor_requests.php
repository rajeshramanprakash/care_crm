<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->string('profile_image_upload')->nullable()->after('profile_image');
            $table->string('profile_image_pending')->nullable()->after('profile_image_upload');
            $table->string('profile_image_status', 32)->default('pending_review')->after('profile_image_pending');
            $table->timestamp('profile_image_reviewed_at')->nullable()->after('profile_image_status');
            $table->unsignedBigInteger('profile_image_reviewed_by')->nullable()->after('profile_image_reviewed_at');
        });

        DB::table('doctor_requests')
            ->whereNotNull('profile_image')
            ->where('profile_image', '!=', '')
            ->update([
                'profile_image_upload' => DB::raw('profile_image'),
                'profile_image_status' => 'approved',
            ]);
    }

    public function down(): void
    {
        Schema::table('doctor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'profile_image_upload',
                'profile_image_pending',
                'profile_image_status',
                'profile_image_reviewed_at',
                'profile_image_reviewed_by',
            ]);
        });
    }
};
