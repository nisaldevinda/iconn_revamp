<?php

namespace App\Console\Commands;

use App\Traits\EmploymentStatusEmailNotificationProcess;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class EmploymentStatusEmailNotificationCommand extends AppCommand
{
    use EmploymentStatusEmailNotificationProcess;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employmentStatusEmailNotificationProcess:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run tenant wise employment status email notification process';

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
            $todayObj = Carbon::now('Pacific/Auckland');

            Log::info(">>> start employment status email notification process >>> ");

            foreach ($this->getTenants() as $tenant) {

                //TODO:: need to implement conditional based execution
                $this->setConnection($tenant);
                $result = $this->handleEmploymentStatusEmailNotificationForTenant($todayObj);

                $infoLog = [
                    'tenantId' => $tenant,
                    'cronJobId' => 3,
                    'description' => json_encode($result)
                ];

                // create employment status email notification process log
                DB::connection('portal')->table('command_info_log')->insert($infoLog);
            }

            Log::info(">>> end employment status email notification process >>> ");
        } catch (Exception $e) {
            // trigger fail event
            $logError = [
                'cronJobId' => 3, // Employment Status Email Notification Cron Job ID
                'exception' => $e->getMessage()
            ];
            DB::connection('portal')->table('command_error_log')->insert($logError);
            Log::critical("Command Error : " . $e->getMessage());
        }
    }
}
