<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('location_services', 'provider_type')) {
            Schema::table('location_services', function (Blueprint $table) {
                $table->string('provider_type', 20)->default('vendor')->after('service_id');
            });
        }

        DB::table('location_services')->whereNull('provider_type')->update(['provider_type' => 'vendor']);

        $indexes = $this->locationServiceIndexNames();

        if (! in_array('loc_svc_provider_unique', $indexes, true)) {
            $this->dropLocationServicesForeignKeys();

            if (in_array('location_services_location_id_service_id_unique', $indexes, true)) {
                Schema::table('location_services', function (Blueprint $table) {
                    $table->dropUnique('location_services_location_id_service_id_unique');
                });
            }

            Schema::table('location_services', function (Blueprint $table) {
                $table->unique(['location_id', 'service_id', 'provider_type'], 'loc_svc_provider_unique');
            });

            $this->restoreLocationServicesForeignKeys();
        }
    }

    public function down(): void
    {
        $indexes = $this->locationServiceIndexNames();

        if (in_array('loc_svc_provider_unique', $indexes, true)) {
            $this->dropLocationServicesForeignKeys();

            Schema::table('location_services', function (Blueprint $table) {
                $table->dropUnique('loc_svc_provider_unique');
            });

            if (! in_array('location_services_location_id_service_id_unique', $indexes, true)) {
                Schema::table('location_services', function (Blueprint $table) {
                    $table->unique(['location_id', 'service_id'], 'location_services_location_id_service_id_unique');
                });
            }

            $this->restoreLocationServicesForeignKeys();
        }

        if (Schema::hasColumn('location_services', 'provider_type')) {
            Schema::table('location_services', function (Blueprint $table) {
                $table->dropColumn('provider_type');
            });
        }
    }

    /** @return list<string> */
    private function locationServiceIndexNames(): array
    {
        return collect(DB::select('SHOW INDEX FROM location_services'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS cnt
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table, $column]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }

    private function dropLocationServicesForeignKeys(): void
    {
        Schema::table('location_services', function (Blueprint $table) {
            if ($this->foreignKeyExists('location_services', 'location_id')) {
                $table->dropForeign(['location_id']);
            }
            if ($this->foreignKeyExists('location_services', 'service_id')) {
                $table->dropForeign(['service_id']);
            }
        });
    }

    private function restoreLocationServicesForeignKeys(): void
    {
        Schema::table('location_services', function (Blueprint $table) {
            if (! $this->foreignKeyExists('location_services', 'location_id')) {
                $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            }
            if (! $this->foreignKeyExists('location_services', 'service_id')) {
                $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            }
        });
    }
};
