<?php

use App\Support\SubAdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (SubAdminPermissions::allPermissionNames() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $userMorph = (new \App\Models\User)->getMorphClass();
        $vendors = Permission::where('name', 'view_vendors')->where('guard_name', 'web')->first();
        $jobRequests = Permission::where('name', 'view_job_requests')->where('guard_name', 'web')->first();

        // The old matrix row "Job Request" saved `view_vendors`; move those grants to the real Job Request permission.
        if ($vendors && $jobRequests) {
            $holders = DB::table('model_has_permissions')
                ->where('permission_id', $vendors->id)
                ->where('model_type', $userMorph)
                ->pluck('model_id');

            foreach ($holders as $userId) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $jobRequests->id,
                    'model_type' => $userMorph,
                    'model_id' => $userId,
                ]);
            }

            DB::table('model_has_permissions')
                ->where('permission_id', $vendors->id)
                ->where('model_type', $userMorph)
                ->delete();
        }

        // Sub Admin access is granted per user by Admin; the role itself must not carry permissions.
        DB::table('role_has_permissions')->where('role_id', SubAdminPermissions::SUB_ADMIN_ROLE_ID)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission grants are data; nothing to roll back safely.
    }
};
