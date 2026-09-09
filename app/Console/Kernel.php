<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate payment invoices daily at 12:01 AM
        $schedule->command('invoices:generate')
                 ->dailyAt('00:01')
                 ->timezone('Asia/Kolkata')
                 ->runInBackground();

        // Fallback if delayed BroadcastFutureProspectReminderJob did not run (queue down). Primary: job at future_prospect_date.
        $schedule->command('leads:dispatch-future-prospect-reminders')
            ->everyMinute()
            ->timezone('Asia/Kolkata');

        $schedule->command('missed-callback:run-due')
            ->everyMinute()
            ->timezone('Asia/Kolkata');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
