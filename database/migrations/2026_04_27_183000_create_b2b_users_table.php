<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile', 20)->unique();
            $table->string('service_requirement');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ensure role exists for consistency in role management.
        $exists = DB::table('roles')->whereRaw('LOWER(name) = ?', ['b2b'])->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'B2B',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_users');
    }
};

