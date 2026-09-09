<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class EnableTicketAccessForCoreRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:enable-core-roles-access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable ticket access for core roles (admin, tpa, vendor)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Core roles: Admin (role_id=1), TPA (role_id=8), Vendor (role_id=10)
        $coreRoleIds = [1, 8, 10];
        $roleNames = [1 => 'Admin', 8 => 'TPA', 10 => 'Vendor'];
        $totalUpdated = 0;

        foreach ($coreRoleIds as $roleId) {
            $users = User::where('role_id', $roleId)->get();

            if ($users->isEmpty()) {
                $this->warn("No users found with role_id '{$roleId}' ({$roleNames[$roleId]})!");
                continue;
            }

            $updatedCount = 0;

            foreach ($users as $user) {
                if (!$user->is_ticket_enabled) {
                    $user->update(['is_ticket_enabled' => true]);
                    $updatedCount++;
                    $this->info("Enabled ticket access for {$roleNames[$roleId]}: {$user->f_name} {$user->l_name}");
                } else {
                    $this->line("{$roleNames[$roleId]} {$user->f_name} {$user->l_name} already has ticket access enabled");
                }
            }

            $totalUpdated += $updatedCount;
            $this->info("Updated {$updatedCount} users with role_id '{$roleId}' ({$roleNames[$roleId]})");
        }

        $this->info("Successfully enabled ticket access for {$totalUpdated} users across all core roles!");

        return 0;
    }
}
