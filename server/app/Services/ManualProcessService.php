<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Jobs\AttendanceProcessJob;
use App\Jobs\LeaveAccrualJob;
use App\Library\Store;
use App\Library\Session;
use Illuminate\Support\Facades\DB;
use stdClass;
use Carbon\Carbon;
class ManualProcessService extends BaseService
{
    private $store;

    private $session;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;
    }


    /**
     * Following function creates a MaritalStatus.
     * 
     * @param $MaritalStatus array containing the MaritalStatus data
     * @return int | String | array
     * 
     * Usage:
     * $MaritalStatus => ["name": "New Level"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "marital status created Successuflly",
     * $data => {"name": "New Level"}//$data has a similar set of values as the input
     **/
    public function run($data)
    {
        try {
            // store in db
            $data['userId'] = $this->session->getUser()->id;
            $data['status'] = "PENDING";

            $processId = $this->store->getFacade()::table('manualProcess')->insertGetId($data);

            $data['processId'] = $processId;
            $data['tenantId'] = $this->session->getTenantId();
            
            $message = '';
            switch ($data['type']) {
                case 'ATTENDANCE_PROCESS':
                    dispatch(new AttendanceProcessJob($data));
                    $message = 'manualProcess.basic.SUCC_CREATE_ATTENDANCE_JOB';
                    break;

                case 'LEAVE_ACCRUAL':
                    dispatch(new LeaveAccrualJob($data));
                    $message = 'manualProcess.basic.SUCC_CREATE_JOB';
                    break;
                
                default:
                    # code...
                    break;
            }
           
            return $this->success(200, Lang::get($message), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('manualProcess.basic.ERR_CREATE_JOB'), null);
        }
    }

    public function history($type)
    {
        try {
            // get company timezone
            $company = DB::table('company')->first('timeZone');
            $companyTimeZone =  $company->timeZone;

            $processHistory = $this->store->getFacade()::table('manualProcess')
                ->select(
                    'manualProcess.id as id',
                    'manualProcess.date as date',
                    'manualProcess.status as status',
                    'manualProcess.createdAt as createdAt',
                    DB::raw("CONCAT_WS(' ', user.firstName, user.lastName) AS employeeName"),
                )
                ->leftJoin('user', 'user.id', '=', 'manualProcess.userId')
                ->where('type', '=', $type)
                ->orderBy('createdAt', 'desc')
                ->get();

            $processedHistoryRecords  = $processHistory->map(function ($item) use($companyTimeZone) {
                    $item->ExecutedAt = Carbon::parse($item->createdAt)->copy()->tz($companyTimeZone);
                    return $item;
                });
                               
            return $this->success(200, Lang::get('manualProcess.basic.SUCC_RETRIVE'),$processedHistoryRecords);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('manualProcess.basic.ERR_RETRIVE'), null);
        }
    }

    public function getLeaveAccrualProcessEmployeeList ($manualProcessId) {
        try {
            $leaveAccrualEmployeeList = $this->store->getFacade()::table('manualProcess')
               ->select(
                'manualProcess.id as id',
                'leaveAccrualProcess.numberOfAllocatedEntitlements',
                'leaveEntitlement.entilementCount',
                'leaveType.name as LeaveTypeName',
                DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName , employee.lastName) AS employeefullName"),
               'employee.employeeNumber'
               )
              ->leftJoin('user', 'user.id', '=', 'manualProcess.userId')
              ->leftJoin('leaveAccrualProcess','leaveAccrualProcess.manualProcessId','=','manualProcess.id')
              ->leftJoin('leaveAccrualEntitlementLog','leaveAccrualEntitlementLog.leaveAccrualProcessId','=','leaveAccrualProcess.id')
              ->leftJoin('leaveEntitlement','leaveEntitlement.id','=','leaveAccrualEntitlementLog.leaveEntitlementId')
              ->leftJoin('leaveAccrual','leaveAccrual.id','=','leaveAccrualProcess.leaveAccrualId')
              ->leftJoin('leaveType','leaveType.id','=','leaveAccrual.leaveTypeId')
              ->leftJoin('employee','employee.id','=','leaveAccrualEntitlementLog.employeeId')
              ->whereNotNull('leaveAccrualProcess.manualProcessId')
              ->whereNotNull('leaveAccrualEntitlementLog.leaveEntitlementId')
              ->where('manualProcess.id', '=', $manualProcessId)
              ->orderBy('employee.employeeNumber')
              ->get();

                               
            return $this->success(200, Lang::get('manualProcess.basic.SUCC_RETRIVE'), $leaveAccrualEmployeeList);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('manualProcess.basic.ERR_RETRIVE'), null);
        }
    }
}
