<?php

namespace App\Services;

use Log;
use Exception;
use DateTime;
use DateInterval;
use DatePeriod;
use Date;
use App\Library\Store;
use App\Library\Util;
use App\Library\Session;
use Illuminate\Support\Facades\Lang;
use App\Exports\LeaveRequestExcelExport;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
use App\Library\FileStore;
use App\Traits\EmployeeHelper;
use App\Traits\ConfigHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;
use App\Library\Redis;
use App\Exports\ExcelExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Events\AttendanceDateDetailChangedEvent;
use App\Traits\AttendanceProcess;


/**
 * Name: ShiftChangeRequestService
 * Purpose: Performs tasks related to the shiftChangeRequest model.
 * Description: Shift Change Request Service class is called by the ShiftChangeRequestController where the requests related
 * to shiftChangeRequest Model (CRUD operations and others).
 * Module Creator: Tharindu Darshana
 */
class ShiftChangeRequestService extends BaseService
{
    use JsonModelReader;
    use EmployeeHelper;
    use AttendanceProcess;
    use ConfigHelper;

    private $store;
    private $session;
    private $redis;
    private $fileStorage;

    private $leaveEntitlementModel;
    private $workflowInstanceModel;
    private $shiftChangeRequestModel;
    private $leaveRequestDetailModel;
    private $leaveRequestEntitlementModel;
    private $userModel;

    private $workflowService;
    private $workCalendarService;
    private $leaveTypeService;

    public function __construct(Store $store, Session $session, FileStore $fileStorage, WorkflowService $ws, Redis $redis, WorkCalendarService $workCalendarService, LeaveTypeService $leaveTypeService)
    {
        $this->store = $store;
        $this->session = $session;
        $this->fileStorage = $fileStorage;
        $this->redis = $redis;

        $this->workflowService = $ws;
        $this->workCalendarService = $workCalendarService;
        $this->leaveTypeService = $leaveTypeService;

        $this->leaveEntitlementModel = $this->getModel('leaveEntitlement', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->shiftChangeRequestModel = $this->getModel('shiftChangeRequest', true);
        $this->leaveRequestDetailModel = $this->getModel('leaveRequestDetail', true);
        $this->leaveRequestEntitlementModel = $this->getModel('leaveRequestEntitlement', true);
        $this->userModel = $this->getModel('user', true);
    }

    /**
     * Following function creates a shift change request. The shift change details that are provided in the Request
     * are extracted and saved to the shift change request table in the database. id is auto genarated 
     *
     * @param $shiftChangeData array containing the shift change data
     * @return int | String | array
     *
     *
     * Sample output:
     * $statusCode => 201,
     * $message => "Short Leave created successfully!",
     *  */
    public function createShiftChangeRequest($shiftChangeData)
    {
        try {
            DB::beginTransaction();
            
            $isAllowWf = true;
            $employeeId = $this->session->getUser()->employeeId;
            $shiftChangeData['employeeId'] = $employeeId;

            $shiftChangeData['workflowInstanceId'] = null;
            $validationResponse = ModelValidator::validate($this->shiftChangeRequestModel, $shiftChangeData);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('shiftChangeRequestMessages.basic.ERR_CREATE'), $validationResponse);
            }

            //check whether already have shift change request for this perticular date
            $relateShiftChangeList = $shorLeaveData = DB::table('shiftChangeRequest')
                ->select('shiftChangeRequest.*')
                ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'shiftChangeRequest.workflowInstanceId')
                ->leftJoin('workflowDefine', 'workflowDefine.id', '=', 'workflowInstance.workflowId')
                ->where('shiftChangeRequest.employeeId', $employeeId)
                ->where('shiftChangeRequest.shiftDate', $shiftChangeData['shiftDate'])->get();

            if (!is_null($relateShiftChangeList)) {
                $inCompleteRequests = 0;
                foreach ($relateShiftChangeList as $key => $value) {
                    if ($value->currentState == 1) { // id no 1 means pending
                        $inCompleteRequests++;
                    }
                }

                if ($inCompleteRequests > 0) {
                    DB::rollback();
                    return $this->error(400, Lang::get('shiftChangeRequestMessages.basic.ERR_ALREADY_HAVE_PENDING_REQUESTS'), []);
                }
            }

            $newShiftChange = $this->store->insert($this->shiftChangeRequestModel, $shiftChangeData, true);

            $dates[] = $shiftChangeData['shiftDate'];

            //check whether self service reuest type is locked
            $hasLockedSelfService = $this->checkSelfServiceRecordLockIsEnable($dates, 'shiftChangeRequest');
            
            if ($hasLockedSelfService) {
                DB::rollback();
                return $this->error(500, Lang::get('shiftChangeRequestMessages.basic.ERR_HAS_LOCKED_SELF_SERVICE'), null);
            }


            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $dates);

            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            if ($isAllowWf) {
                $shiftChangeDataSet = (array) $this->store->getById($this->shiftChangeRequestModel, $newShiftChange['id']);
                if (is_null($shiftChangeDataSet)) {
                    return $this->error(404, Lang::get('shiftChangeRequestMessages.basic.ERR_CREATE'), $newShiftChange['id']);
                }
                // this is the workflow context id related for Apply Short Leave
                $context = 5;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $shiftChangeDataSet, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                $shiftChangeRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateShiftChangeRequest = $this->store->updateById($this->shiftChangeRequestModel, $newShiftChange['id'], $shiftChangeRequstUpdated);
                if (!$updateShiftChangeRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('shiftChangeRequestMessages.basic.ERR_CREATE'), $newShiftChange['id']);
                }
            }

            DB::commit();
            return $this->success(201, Lang::get('shiftChangeRequestMessages.basic.SUCC_CREATE'), $shiftChangeDataSet);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return $this->error(500, Lang::get('shiftChangeRequestMessages.basic.ERR_CREATE'), null);
        }
    }
}
