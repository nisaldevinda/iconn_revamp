<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

class ScheduledJobLogsService extends BaseService
{
    private $store;

    private $session;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;
    }
    /** 
     * Following function retrives all scheduledJobsLog History .
     * Usage:
     * $scheduledJobs => "attendnaceLogs"
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Scheduled Jobs loaded successfully",
     *      $data => [{"id": 1, "status": "0","exception": "" ,"createdAt" : "2022-02-10"}, {"id": 2, "status": "0","exception": "" ,"createdAt" : "2022-02-10"}]
     * ] 
     */
    public function getScheduledJobsLogsHistory($scheduledJobs) {
        try {
            $type = $scheduledJobs['type'];
            $status = json_decode($scheduledJobs['status']);
            $date = $scheduledJobs['date'];
       
            $tenantId = $this->session->getTenantId();
        
            if ($type === "attendanceLogs") {
                $attendnaceLogHistory =  DB::connection('portal')->table('attendance_process_log')
                  ->where('tenantId',$tenantId);
            }

            if ($type === "leaveAccrualLogs") {
                $attendnaceLogHistory =  DB::connection('portal')->table('leave_accrual_log')
                    ->where('tenantId',$tenantId);
                    
            }

            if (!empty($date)) {
                $attendnaceLogHistory = $attendnaceLogHistory->whereDate('createdAt', '=', Carbon::parse($date)->toDateString());
            }

            if (!empty($status)) {
                $attendnaceLogHistory = $attendnaceLogHistory->whereIn('hasFailed', $status);
            }
            $attendnaceLogHistory = $attendnaceLogHistory->orderBy('createdAt', 'desc')->get();
            return $this->success(200, Lang::get('scheduledJobLogMessages.basic.SUCC_RETRIVE'), $attendnaceLogHistory);
        }   catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('scheduledJobLogMessages.basic.ERR_RETRIVE'), null);
        }
    }
    
}
