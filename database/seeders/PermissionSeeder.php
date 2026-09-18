<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'view_dashboard',
            'view_payments',
            'view_leads',
            'view_referral_leads',
            'view_user',
            'create_user',
            'edit_user',
            'delete_user',
            'view_b2b_users',
            'view_b2b_corporate',
            'view_insurers',
            'view_break_logs',
            'view_duty_logs',
            'view_operation_leads',
            'view_locations',
            'view_services',
            'view_languages',
            'view_agreements',
            'view_doctor_requests',
            'view_chats',
            'view_whatsapp',
            'view_vendors',
            'view_bulk_registration',
            'view_technical_support'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to Admin role if it exists
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
        }
    }
}
