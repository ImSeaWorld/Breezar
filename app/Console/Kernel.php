<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Sync Fly.io instances every 30 minutes
        $schedule->command('fly:sync')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/fly-sync.log'));
            
        // Clean up old login logs (older than 30 days)
        $schedule->call(function () {
            \App\Models\LoginLog::where('timestamp', '<', now()->subDays(30))->delete();
        })->daily();
        
        // Clean up old activity logs (older than 90 days)
        $schedule->call(function () {
            \App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
        })->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
