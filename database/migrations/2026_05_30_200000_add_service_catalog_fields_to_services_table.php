<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('services', 'icon_path')) {
                $table->string('icon_path', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('services', 'consultation_duration_minutes')) {
                $table->unsignedInteger('consultation_duration_minutes')->default(30)->after('icon_path');
            }
            if (! Schema::hasColumn('services', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('consultation_duration_minutes');
            }
            if (! Schema::hasColumn('services', 'specialization_options')) {
                $table->json('specialization_options')->nullable()->after('sort_order');
            }
            if (! Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('specialization_options');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = [
                'description',
                'icon_path',
                'consultation_duration_minutes',
                'sort_order',
                'specialization_options',
                'is_active',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
