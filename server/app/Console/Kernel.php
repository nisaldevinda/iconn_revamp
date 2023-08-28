<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Laravelista\LumenVendorPublish\VendorPublishCommand::class,
        Commands\TestCommand::class,
        Commands\SendEmailForMissingAttendanceCommand::class,
        Commands\AttendanceProcessCommand::class,
        Commands\LeaveAccrualCommand::class,
        Commands\DatabaseMigrationCommand::class,
        Commands\CreateDatabaseWithSampleData::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('attendanceProcess:run')->everyFifteenMinutes()->withoutOverlapping()->appendOutputTo(storage_path('logs/attendance.log'));
        $schedule->command('leaveAccrual:run')->everyThirtyMinutes()->withoutOverlapping()->appendOutputTo(storage_path('logs/leave-accrual.log'));
        $schedule->command('employmentStatusEmailNotificationProcess:run')->daily()->withoutOverlapping()->appendOutputTo(storage_path('logs/employment-status-email-notification.log'));
        $schedule->command('missingAttendanceEmail:run')->everyFourHours($minutes = 0)->withoutOverlapping()->appendOutputTo(storage_path('logs/missing-attendance-email-notification.log'));
    }
}
