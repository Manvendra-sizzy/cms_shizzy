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
        // Keep logs lean: retain only recent 15 days.
        $schedule->command('cms:logs:prune --days=15')->dailyAt('01:10');
        $schedule->command('hrms:attendance:compliance')->dailyAt('09:00');
        $schedule->command('cms:telegram:daily-attendance')
            ->dailyAt('09:15')
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
