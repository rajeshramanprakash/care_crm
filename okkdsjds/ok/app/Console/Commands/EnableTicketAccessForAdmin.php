<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class EnableTicketAccessForAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:enable-admin-access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable ticket access for admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Admin role_id = 1
        $adminUsers = User::where('role_id', 1)->get();

        if ($adminUsers->isEmpty()) {
            $this->warn('No admin users found!');
            return 0;
        }

        $updatedCount = 0;

        foreach ($adminUsers as $admin) {
            if (!$admin->is_ticket_enabled) {
                $admin->update(['is_ticket_enabled' => true]);
                $updatedCount++;
                $this->info("Enabled ticket access for admin: {$admin->f_name} {$admin->l_name}");
            } else {
                $this->line("Admin {$admin->f_name} {$admin->l_name} already has ticket access enabled");
            }
        }

        $this->info("Successfully enabled ticket access for {$updatedCount} admin users!");

        return 0;
    }
}
