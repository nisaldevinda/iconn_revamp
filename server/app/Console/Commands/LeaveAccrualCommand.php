<?php

namespace App\Console\Commands;

use App\Traits\JobResponser;
use App\Traits\LeaveAccrual;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class LeaveAccrualCommand extends AppCommand
{
    use LeaveAccrual;
    use JobResponser;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaveAccrual:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run tenant wise leave accrual';

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

            Log::info(">>> start accrual process >>> ");

            foreach($this->getTenants() as $tenant) {

                //TODO:: need to implement conditional based execution
                $this->setConnection($tenant);
                $result = $this->accrualProcess($todayObj);

                $attendanceLog = [
                    'tenantId' => $tenant,
                    'hasFailed' => $result['error'],
                    'exception' => $result['message']
                ];

                // create attendance process log
                DB::connection('portal')->table('leave_accrual_log')->insert($attendanceLog);
            }

            Log::info(">>> end accrual process >>> ");

        } catch (Exception $e) {
            // trigger fail event
            $logError = [
                'cronJobId' => 2, // attendance cron job id
                'exception' => $e->getMessage()
            ];
            DB::connection('portal')->table('command_error_log')->insert($logError);
            Log::critical("Command Error : " . $e->getMessage());
        }
    }

}