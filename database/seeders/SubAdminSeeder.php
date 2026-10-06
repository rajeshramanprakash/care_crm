<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SubAdminSeeder extends Seeder
{
    public function run()
    {
        // Check if ID 7 exists, if not, create it with ID 7
        $role = Role::find(7);
        if (!$role) {
            DB::table('roles')->insert([
                'id' => 7,
                'name' => 'Sub Admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $role = Role::find(7);
        }

        // Sub Admin permissions are granted per user from Admin > Users, never on the role.
        $role->syncPermissions([]);

        // Create a default Sub Admin User
        $user = User::firstOrCreate(
            ['email' => 'subadmin@care.com'],
            [
                'f_name' => 'Sub',
                'l_name' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => 7,
                'is_active' => '1',
                'mobile' => '9999999999'
            ]
        );

        // Assign Spatie role
        $user->assignRole($role);
    }
}
