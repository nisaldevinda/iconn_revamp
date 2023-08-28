<?php

namespace App\Console\Commands;

use App\Traits\DailyMissingAttendanceNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SendEmailForMissingAttendanceCommand extends AppCommand
{
    use DailyMissingAttendanceNotification;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missingAttendanceEmail:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run tenant wise to send emails for missing daily attendace clock in / clock out';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // get current date & time instance in Pacific/Auckland
            $currentDateTimeObj = Carbon::now();

            Log::info(">>> start missing attendance sending email process >>> ");

            foreach ($this->getTenants() as $tenant) {

                Log::info(">>> tenant >>> ".$tenant);
                Log::info(">>> Running Time >>> ".$currentDateTimeObj );

                // //TODO:: need to implement conditional based execution
                $this->setConnection($tenant);
                $result = $this->processEmailForMissigAttendance($currentDateTimeObj);

                $attendanceLog = [
                    'tenantId' => $tenant,
                    'hasFailed' => $result['error'],
                    'exception' => $result['message']
                ];

                // create attendance process log
                DB::connection('portal')->table('missing_attendance_email_notify_log')->insert($attendanceLog);
            }

            Log::info(">>> end missing attendance sending email process >>> ");
        } catch (Exception $e) {
            // trigger fail event
            $logError = [
                'cronJobId' => 1, // attendance cron job id
                'exception' => $e->getMessage()
            ];
            DB::connection('portal')->table('command_error_log')->insert($logError);
            Log::critical("Command Error : " . $e->getMessage());
        }
    }
}
