<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('b2b_users') || ! Schema::hasColumn('b2b_users', 'account_type')) {
            return;
        }

        DB::table('b2b_users')->update(['account_type' => 'legacy']);
    }

    public function down(): void
    {
        // no-op: cannot safely restore prior account_type values
    }
};
