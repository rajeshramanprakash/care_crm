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
            'create_b2b_corporate',
            'create_b2b_individual',
            'create_b2b_users',
            'create_broker',
            'create_insurer',
            'create_lead',
            'create_payment',
            'create_user',
            'delete_b2b_corporate',
            'delete_b2b_individual',
            'delete_b2b_users',
            'delete_broker',
            'delete_insurer',
            'delete_lead',
            'delete_user',
            'edit_b2b_corporate',
            'edit_b2b_individual',
            'edit_b2b_users',
            'edit_broker',
            'edit_insurer',
            'edit_lead',
            'edit_user',
            'view_agreements',
            'view_b2b_corporate',
            'view_b2b_individual',
            'view_b2b_users',
            'view_break_logs',
            'view_brokers',
            'view_bulk_registration',
            'view_chats',
            'view_dashboard',
            'view_doctor_requests',
            'view_duty_logs',
            'view_insurers',
            'view_languages',
            'view_lead_details',
            'view_leads',
            'view_locations',
            'view_operation_leads',
            'view_payments',
            'view_referral_leads',
            'view_services',
            'view_technical_support',
            'view_user',
            'view_vendors',
            'view_whatsapp'
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
