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
 * Name: ShortLeaveRequestService
 * Purpose: Performs tasks related to the shortLeaveRequest model.
 * Description: Short Leave Service class is called by the ShortLeaveRequestController where the requests related
 * to User shortLeaveRequest Model (CRUD operations and others).
 * Module Creator: Tharindu Darshana
 */
class ShortLeaveRequestService extends BaseService
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
    private $shortLeaveRequestModel;
    private $leaveRequestDetailModel;
    private $leaveRequestEntitlementModel;
    private $cancelShortLeaveRequestModel;
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
        $this->cancelShortLeaveRequestModel = $this->getModel('cancelShortLeaveRequest', true);
        $this->leaveEntitlementModel = $this->getModel('leaveEntitlement', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
        $this->leaveRequestDetailModel = $this->getModel('leaveRequestDetail', true);
        $this->leaveRequestEntitlementModel = $this->getModel('leaveRequestEntitlement', true);
        $this->userModel = $this->getModel('user', true);
    }

    /**
     * Following function creates a short leave. The short leave details that are provided in the Request
     * are extracted and saved to the short leave request table in the database. id is auto genarated 
     *
     * @param $shortLeave array containing the leave type data
     * @return int | String | array
     *
     *
     * Sample output:
     * $statusCode => 201,
     * $message => "Short Leave created successfully!",
     *  */
    public function createShortLeave($shortLeave, $requestType = null)
    {
        try {
            DB::beginTransaction();
            $isAllowWf = true;

            $employeeId ='';
            if (isset($shortLeave['employeeId'])) {
                $employeeId = $shortLeave['employeeId'];
            } else {
                $employeeId = $this->session->getUser()->employeeId;
            }
            $shortLeave['employeeId'] = $employeeId;
            $shortLeave['workflowInstanceId'] = null;
            $shortLeave['numberOfMinutes'] = (string) $this->getConfigValue('short_leave_duration');
            $validationResponse = ModelValidator::validate($this->shortLeaveRequestModel, $shortLeave);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $validationResponse);
            }

            //check whether monthly short leave allocation is over
            $shortLeaveValidationData = $this->getShortLeaveValidationData($employeeId, $shortLeave['date']);

            $monthlyShortLeaveAllocation = $this->getConfigValue('monthly_short_leave_allocation');

            if ($shortLeaveValidationData['hasShortLeaveForSameDate']) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_HAS_SHORT_LEAVE_FOR_SAME_DATE'), []);
            }

            if ($shortLeaveValidationData['usedShortLeaveCount'] >= $monthlyShortLeaveAllocation) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_NOT_ENOUGH_MONTHLY_SHORT_LEAVE_ALLOCATIONS'), []);
            }


            $newLeave = $this->store->insert($this->shortLeaveRequestModel, $shortLeave, true);

            // save attachment and update the leave Request Record
            if (sizeof($shortLeave['attachmentList']) > 0) {
                $attachmentIds = [];
                foreach ($shortLeave['attachmentList'] as $key2 => $attachmentData) {
                    $attachmentData = (array) $attachmentData;

                    $file = $this->fileStorage->putBase64EncodedObject(
                        $attachmentData['fileName'],
                        $attachmentData['fileSize'],
                        $attachmentData["data"]
                    );

                    if (empty($file->id)) {
                        DB::rollback();
                        return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), null);
                    }

                    $attachmentIds[] = $file->id;
                }

                $leaveRequstUpdated['fileAttachementIds'] = json_encode($attachmentIds, true);
                $updateLeaveRequest = $this->store->updateById($this->shortLeaveRequestModel, $newLeave['id'], $leaveRequstUpdated);
            }

            $leaveDates[] = $shortLeave['date'];

            //check whether self service reuest type is locked
            $hasLockedSelfService = $this->checkSelfServiceRecordLockIsEnable($leaveDates, $requestType);
            
            if ($hasLockedSelfService) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_SELF_SERVICE'), null);
            }

            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);

            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            if ($isAllowWf) {
                $shortLeaveDataSet = (array) $this->store->getById($this->shortLeaveRequestModel, $newLeave['id']);
                if (is_null($shortLeaveDataSet)) {
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $newLeave['id']);
                }
                // this is the workflow context id related for Apply Short Leave
                $context = 4;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $shortLeaveDataSet, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                $leaveRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateLeaveRequest = $this->store->updateById($this->shortLeaveRequestModel, $newLeave['id'], $leaveRequstUpdated);
                if (!$updateLeaveRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $newLeave['id']);
                }
            }

            $dataSet = [
                'employeeId' => $employeeId,
                'dates' => $leaveDates
            ];
            event(new AttendanceDateDetailChangedEvent($dataSet));

            DB::commit();
            return $this->success(201, Lang::get('leaveRequestMessages.basic.SUCC_CREATE'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function return the validation data of the particular short leave
     *
     *  */
    private function getShortLeaveValidationData($employeeId, $date)
    {
        $carbonDate = Carbon::parse($date);
        $startOfMonth = $carbonDate->startOfMonth()->format('Y-m-d');
        $endOfMonth = $carbonDate->endOfMonth()->format('Y-m-d');

        $shortLeaveList = $shorLeaveData = DB::table('shortLeaveRequest')
            ->where('shortLeaveRequest.employeeId', $employeeId)
            ->whereBetween('shortLeaveRequest.date', array($startOfMonth, $endOfMonth))->get();

        $usedShortLeaveCount = 0;
        $hasShortLeaveForSameDate = false;

        foreach ($shortLeaveList as $key => $shortLeave) {
            $shortLeave = (array) $shortLeave;

            if (empty($shortLeave['workflowInstanceId'])) {
                if ($date === $shortLeave['date'] && in_array($shortLeave['currentState'], [1,2])) {
                    $hasShortLeaveForSameDate = true;
                }

                if (in_array($shortLeave['currentState'], [1,2])) {
                    $usedShortLeaveCount++;
                }
            } else {
                $instanceData = DB::table('workflowInstance')
                    ->where('workflowInstance.id', $shortLeave['workflowInstanceId'])
                    ->where('workflowInstance.isDelete', false)->first();
                $instanceData = (array) $instanceData;
                $priorState = $instanceData['currentStateId'];

                //check whether the workflow in failure state
                $workflowData = DB::table('workflowDefine')
                    ->where('workflowDefine.id', $instanceData['workflowId'])
                    ->where('workflowDefine.isDelete', false)->first();

                $workflowData = (array) $workflowData;
                $failureStates = [3,4];

                if (!in_array($priorState, $failureStates)) {
                    if ($date === $shortLeave['date']) {
                        $hasShortLeaveForSameDate = true;
                    }
                    $usedShortLeaveCount++;
                }
            }
        }

        $validationData = [
            'hasShortLeaveForSameDate' => $hasShortLeaveForSameDate,
            'usedShortLeaveCount' => $usedShortLeaveCount
        ];

        return $validationData;
    }


    /**
     * Following function retrives all short leaves.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All short leaves retrieved Successfully!",
     *      $data => [{"date": "2022-09-15", ...}, ...]
     * ]
     */
    public function getAllShortLeaves($permittedFields, $options)
    {
        try {
            $filteredLeaves = $this->store->getAll(
                $this->shortLeaveRequestModel,
                $permittedFields,
                $options,
                [],
                []
            );

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GETALL'), $filteredLeaves);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single short leave for a provided id.
     *
     * @param $id user short leave id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Leave retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getShortLeave($id)
    {
        try {
            $leave = $this->store->getById($this->shortLeaveRequestModel, $id);
            if (is_null($leave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leave);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_GET'), null);
        }
    }


    /**
     * Following function for assign a short leave to particular employee. The short leave details that are provided in the Request
     * are extracted and saved to the short leave request table in the database. id is auto genarated 
     *
     * @param $shortLeaveData array containing the leave type data
     * @return int | String | array
     *
     *
     * Sample output:
     * $statusCode => 201,
     * $message => "Short Leave assigned successfully!",
     *  */

    public function assignShortLeave($shortLeaveData)
    {
        try {
            DB::beginTransaction();

            $employeeId = $shortLeaveData['employeeId'];
            $shortLeaveData['workflowInstanceId'] = null;
            $validationResponse = ModelValidator::validate($this->shortLeaveRequestModel, $shortLeaveData);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $validationResponse);
            }

            //check whether monthly short leave allocation is over
            $shortLeaveValidationData = $this->getShortLeaveValidationData($employeeId, $shortLeaveData['date']);

            $monthlyShortLeaveAllocation = $this->getConfigValue('monthly_short_leave_allocation');

            if ($shortLeaveValidationData['hasShortLeaveForSameDate']) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_HAS_SHORT_LEAVE_FOR_SAME_DATE'), []);
            }

            if ($shortLeaveValidationData['usedShortLeaveCount'] >= $monthlyShortLeaveAllocation) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_NOT_ENOUGH_MONTHLY_SHORT_LEAVE_ALLOCATIONS'), []);
            }

            $shortLeaveData['currentState'] = 2;
            $shortLeaveData['approvedBy'] = $this->session->getUser()->id;
            $shortLeaveData['approvedAt'] = Carbon::now()->toDateTimeString();

            $newLeave = $this->store->insert($this->shortLeaveRequestModel, $shortLeaveData, true);

            $leaveDates[] = $shortLeaveData['date'];
            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);

            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            // save attachment and update the leave Request Record
            if (sizeof($shortLeaveData['attachmentList']) > 0) {
                $attachmentIds = [];
                foreach ($shortLeaveData['attachmentList'] as $key2 => $attachmentData) {
                    $attachmentData = (array) $attachmentData;

                    $file = $this->fileStorage->putBase64EncodedObject(
                        $attachmentData['fileName'],
                        $attachmentData['fileSize'],
                        $attachmentData["data"]
                    );

                    if (empty($file->id)) {
                        DB::rollback();
                        error_log($e->getMessage());
                        return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), null);
                    }

                    $attachmentIds[] = $file->id;
                }

                $leaveRequstUpdated['fileAttachementIds'] = json_encode($attachmentIds, true);
                $updateLeaveRequest = $this->store->updateById($this->shortLeaveRequestModel, $newLeave['id'], $leaveRequstUpdated);
            }

            $dataSet = [
                'employeeId' => $employeeId,
                'dates' => $leaveDates
            ];
            event(new AttendanceDateDetailChangedEvent($dataSet));

            DB::commit();
            return $this->success(201, Lang::get('leaveRequestMessages.basic.SUCC_CREATE'), $newLeave);
        } catch (Exception $e) {
            DB::rollback();
            error_log($e->getMessage());
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrive the short leave related attchments.
     *
     * @param $shortLeave array containing the leave type data
     * @return | array
     *
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Short Leave attachments retrive successfully !",
     *  */
    public function getShortLeaveAttachments($id)
    {
        try {
            //get realted leave
            $shortLeaveDataSet = (array) $this->store->getById($this->shortLeaveRequestModel, $id);
            if (is_null($shortLeaveDataSet)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $id);
            }

            $shortLeaveDataSet = (array) $shortLeaveDataSet;
            $fileAttchements = json_decode($shortLeaveDataSet['fileAttachementIds']);
            $attachments = DB::table('fileStoreObject')
                ->select('*')
                ->whereIn('id', $fileAttchements)
                ->get();


            foreach ($attachments as $key => $value) {
                $value = (array) $value;
                $base64 = $file = $this->fileStorage->getBase64EncodedObject($value['id']);
                $attachments[$key]->data = $base64->data;
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_RETRIVE_ATTACHMENTS'), $attachments);
        } catch (Exception $e) {
            DB::rollback();
            error_log($e->getMessage());
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_RETRIVE_ATTACHMENTS'), null);
        }
    }


    /**
     * Following function retrive the employee short leave history
     *
     * @return int | String | array
     *
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Short Leave history successfully!",
     *  */
    public function getEmployeeShortLeavesHistoryData($request)
    {
        try {
            $employeeId = $this->session->getEmployee()->id;

            if (!$employeeId) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_GET'));
            }

            $date = $request->query('date', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $accessType = 'employee';
            $filter = $request->query('filter', null);
            $filter = json_decode($filter);
            $searchString = $request->query('searchString', null);

            $leaveRequests = $this->getShortLeaveRequestData($employeeId, $date, $pageNo, $pageCount, $sort, $accessType, $filter, $searchString);

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function retrive the  short leave history emoloyees that relate to particular admin
     *
     * @return int | String | array
     *
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Short Leave history successfully!",
     *  */
    public function getAdminShortLeavesHistoryData($request)
    {
        try {
            
            $employeeId = $request->query('employee', null);
            $date = $request->query('date', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $accessType = 'employee';
            $filter = $request->query('filter', null);
            $filter = json_decode($filter);
            $searchString = $request->query('searchString', null);

            if (empty($employeeId)) {
                $employeeId = $this->session->getContext()->getAdminPermittedEmployeeIds();
            }

            $leaveRequests = $this->getShortLeaveRequestData($employeeId, $date, $pageNo, $pageCount, $sort, $accessType, $filter, $searchString);
            // $leaveRequests = [];

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    /**
     * Following function retrive the  short leave history list for given employees
     *
     * @return int | String | array
     *
     *
     * Sample output:[count => 20 , sheets => ["date" => 2022-09-05, shortLeaveType => 'IN_SHORT_LEAVE']]
     *  */
    private function getShortLeaveRequestData($employeeId, $date, $pageNo, $pageCount, $sort, $accessType, $filter, $searchString)
    {
        try {

            if (!empty($date)) {
                $date = strtotime($date);
                $date = date('Y-m-d', $date);
            }

            $allSucessStates =[2];
            $allFailiureStates = [3, 4];

            $leaveRequests = DB::table('shortLeaveRequest')
                ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'shortLeaveRequest.workflowInstanceId')
                ->leftJoin('employee', 'employee.id', '=', 'shortLeaveRequest.employeeId')
                ->leftJoin('workflowState', 'workflowState.id', '=', 'shortLeaveRequest.currentState')
                ->selectRaw("CONCAT_WS(' ', firstName,  lastName) AS employeeName, employee.firstName, employee.lastName, employee.id as employeeIdNo")
                ->selectRaw('shortLeaveRequest.id,
                shortLeaveRequest.numberOfMinutes,
                shortLeaveRequest.date,
                shortLeaveRequest.fromTime,
                shortLeaveRequest.toTime,
                shortLeaveRequest.createdAt as requestedDate,
                shortLeaveRequest.approvedAt,
                shortLeaveRequest.approvedBy,
                shortLeaveRequest.reason,
                shortLeaveRequest.shortLeaveType,shortLeaveRequest.currentState')
                ->selectRaw('workflowInstance.currentStateId,
                workflowInstance.workflowId,
                workflowInstance.id as workflowInstanceIdNo,
                workflowState.label as StateLabel');

            if (is_array($employeeId)) {
                $leaveRequests = $leaveRequests->whereIn('shortLeaveRequest.employeeId', $employeeId);
            } else {
                $leaveRequests = $leaveRequests->where('shortLeaveRequest.employeeId', $employeeId);
            }

            if (!empty($searchString)) {
                $leaveRequests = $leaveRequests->where('shortLeaveRequest.date', 'like', '%' . $searchString . '%');
            }

            if (!empty($filter->shortLeaveType)) {
                $searchArr = $filter->shortLeaveType;
                $leaveRequests = $leaveRequests->whereIn('shortLeaveRequest.shortLeaveType', $searchArr);
            }

            if (!empty($filter->StateLabel)) {
                $searchArr = $filter->StateLabel;

                $successAndFailiureStatus = array_merge($allSucessStates, $allFailiureStates);
                $pendingStates = [1];
                
                $filterStates = [];

                foreach ($searchArr as $key => $value) {
                    switch ($value) {
                        case 'Pending':
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, $pendingStates) : $pendingStates; 
                            break;
                        case 'Approved':
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, $allSucessStates) : $allSucessStates; 
                            break;
                        case 'Rejected':
                            //assume use default Reject state that default provide by system
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, [3]) : [3]; 
                            break;
                        case 'Cancelled':
                            //assume use default Cancel state that default provide by system
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, [4]) : [4]; 
                            break;
                        default:
                            # code...
                            break;
                    }
                }

                $leaveRequests = $leaveRequests->whereIn('shortLeaveRequest.currentState', $filterStates);

            }


            $leaveRequestCount = $leaveRequests->count();


            if (empty($sort)) {
                $leaveRequests = $leaveRequests->orderBy("shortLeaveRequest.date", 'DESC');
            } else {
                if (!empty($sort->name) && !empty($sort->order)) {

                    switch ($sort->name) {
                        case 'date':
                            $leaveRequests = $leaveRequests->orderBy("shortLeaveRequest.date", $sort->order);
                            break;
                        case 'id':
                            $leaveRequests = $leaveRequests->orderBy("shortLeaveRequest.id", $sort->order);
                            break;
                        default:
                            $leaveRequests = $leaveRequests->orderBy("shortLeaveRequest.date", $sort->order);
                            break;
                    }
                } else {
                    $leaveRequests = $leaveRequests->orderBy("shortLeaveRequest.date", 'DESC');
                }
            }


            if ($pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $leaveRequests = $leaveRequests->skip($skip)->take($pageCount);
            }

            $leaveRequests = $leaveRequests->get();

            $leaveRequestsArray = [];

            foreach ($leaveRequests as $key => $leave) {
                $leave = (array) $leave;
                $date = strtotime($leave['date']);
                $date = date('Y-m-d', $date);
                $leave['date'] = $date;


                array_push($leaveRequestsArray, $leave);
            }
            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;

            foreach ($leaveRequestsArray as $leaveRequestIndex => $leaveRequest) {
                $leaveRequest = (array) $leaveRequest;

                if ($leaveRequest['approvedAt'] === '0000-00-00 00:00:00') {
                    $leaveRequestsArray[$leaveRequestIndex]['approvedAt'] = '-';
                }

                if (!empty($leaveRequest['approvedBy'])) {
                    $userData = (array) $this->store->getById($this->userModel, $leaveRequest['approvedBy']);

                    if (is_null($userData)) {
                        $leaveRequestsArray[$leaveRequestIndex]['approvedBy'] = '-';
                    } else {
                        $leaveRequestsArray[$leaveRequestIndex]['approvedBy'] = $userData['employeeName'];
                    }
                }
                $leaveRequestsArray[$leaveRequestIndex]['canDirectCancelByAdmin'] = false;

                if (empty($leaveRequest['workflowId'])) {
                    if ($leaveRequest['currentState'] == 2) {
                        $leaveRequestsArray[$leaveRequestIndex]['canDirectCancelByAdmin'] = true;
                    }
                }


                $leaveRequestsArray[$leaveRequestIndex]['approvedAt'] = $this->getFormattedDateForList($leaveRequest['approvedAt'], $timeZone);
                $leaveRequestsArray[$leaveRequestIndex]['requestedDate'] = $this->getFormattedDateForList($leaveRequest['requestedDate'], $timeZone);
                $leaveRequestsArray[$leaveRequestIndex]['fromTime'] = Carbon::parse($leaveRequest['fromTime'])->format('g:i A');
                $leaveRequestsArray[$leaveRequestIndex]['toTime'] = Carbon::parse($leaveRequest['toTime'])->format('g:i A');
                $leaveRequestsArray[$leaveRequestIndex]['date'] = Carbon::parse($leaveRequest['date'])->format('d-m-Y');


                if (!in_array($leaveRequest['currentState'], $allSucessStates) && !in_array($leaveRequest['currentState'], $allFailiureStates)) {
                    $leaveRequestsArray[$leaveRequestIndex]['StateLabel'] = 'Pending'; 
                    $leaveRequestsArray[$leaveRequestIndex]['stateTagColor'] = 'orange';  
                } elseif (in_array($leaveRequest['currentState'], $allSucessStates) && !in_array($leaveRequest['currentState'], $allFailiureStates)) {
                    $leaveRequestsArray[$leaveRequestIndex]['StateLabel'] = 'Approved';   
                    $leaveRequestsArray[$leaveRequestIndex]['stateTagColor'] = 'green';
                } elseif (!in_array($leaveRequest['currentState'], $allSucessStates) && in_array($leaveRequest['currentState'], $allFailiureStates) && $leaveRequest['currentState'] == 3) {
                    $leaveRequestsArray[$leaveRequestIndex]['StateLabel'] = 'Rejected';
                    $leaveRequestsArray[$leaveRequestIndex]['stateTagColor'] = 'red';
                } elseif (!in_array($leaveRequest['currentState'], $allSucessStates) && in_array($leaveRequest['currentState'], $allFailiureStates) && $leaveRequest['currentState'] == 4) {
                    $leaveRequestsArray[$leaveRequestIndex]['StateLabel'] = 'Cancelled';
                    $leaveRequestsArray[$leaveRequestIndex]['stateTagColor'] = 'geekblue';
                }
            }

            $responce = new stdClass();
            $responce->count = $leaveRequestCount;
            $responce->sheets = $leaveRequestsArray;
            $responce->success = true;

            return $responce;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function return the formatted date for given time stamp
     *
     * @return | String 
     *  */
    private function getFormattedDateForList($date, $timeZone)
    {
        try {
            $formattedDate = '-';
            if (!empty($date) && $date !== '-' && $date !== '0000-00-00 00:00:00') {
                
                $formattedDate = Carbon::parse($date, 'UTC')->copy()->tz($timeZone);
                $approvedAtArr = explode(' ', $formattedDate);
                if (!empty($approvedAtArr) && sizeof($approvedAtArr) >= 2) {
                    $formattedTime = Carbon::parse($approvedAtArr[1]);

                    $formattedDate = Carbon::parse($approvedAtArr[0])->format('d-m-Y') . ' at ' . $formattedTime->format('g:i A');
                }
            }
            
            return $formattedDate;
        } catch (\Exception $e) {
            echo 'invalid date';
        }
    }

    /**
     * Following function return whether give date is working date or not
     *
     * @return | array 
     *  */
    public function getShortLeaveDateIsWorkingDay($date, $employeeId = null)
    {
        try {
            if (is_null($employeeId)) {
                $employeeId = $this->session->getUser()->employeeId;
            }

            $isWorkingDay = 0;
            $startDate = new DateTime($date);
            $interval = new DateInterval('P1D');
            $endDate = new DateTime($date);
            $endDate = $endDate->add($interval);
            $period = new DatePeriod(
                $startDate,
                $interval,
                $endDate
            );

            foreach ($period as $dateObject) {
                $shift = $this->getEmployeeWorkShift($employeeId, $dateObject);

                if (!empty($shift) && $shift->noOfDay > 0) {
                    $isWorkingDay = true;
                }
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_ADD_COMMENT'), ['isWorkingDay' => $isWorkingDay]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function return whether given date is working day in employee calender or not
     *
     * @return | boolean 
     *  */
    private function isCalendarWorkingDay($employeeCalendar, $date)
    {
        $specialDay = $this->store->getFacade()::table('workCalendarSpecialDays')
            ->where('calendarId', $employeeCalendar[0]->calendarId)
            ->where('date', $date)
            ->first();

        $dayTypeId = 0;

        if (!empty($specialDay)) {
            $dayTypeId = $specialDay->workCalendarDayTypeId;
        } else {
            $dayTextualRepresentation = $date->format('l');
            $employeeCalendarDay = current(array_filter($employeeCalendar, function ($day) use ($dayTextualRepresentation) {
                return (strtolower($day->dayName) == strtolower($dayTextualRepresentation));
            }));

            $dayTypeId = !empty($employeeCalendarDay) ? $employeeCalendarDay->dayTypeId : 0;
        }

        return $dayTypeId == 1;
    }

    /**
     * Following function return all the success states ids that related with short leave related workflows
     *
     * @return | array 
     *  */
    private function getAllShortLeaveRelateSuccessStates() 
    {
        $workflows = DB::table('workflowDefine')->select('sucessStates')->where('contextId', 4)->get();
        $states = [];
        foreach ($workflows as $key => $value) {
            $sucessStatesArr = json_decode($value->sucessStates);
            $states =  array_merge($states, $sucessStatesArr);
        }

        return array_unique($states);
    }

    /**
     * Following function return all the failier states ids that related with short leave related workflows
     *
     * @return | array 
     *  */
    private function getAllShortLeaveRelateFaliureStates() 
    {
        $workflows = DB::table('workflowDefine')->select('failureStates')->where('contextId', 4)->get();
        $states = [];
        foreach ($workflows as $key => $value) {
            $sucessStatesArr = json_decode($value->failureStates);
            $states =  array_merge($states, $sucessStatesArr);
        }

        return array_unique($states);
    }

    /**
     * Following function return all the pending states ids that related with short leave related workflows
     *
     * @return | array 
     *  */
    private function getAllShortLeaveRelatePendingStates($successAndFailiureStatus) 
    {
        $workflowStatesTransitions = DB::table('workflowStateTransitions')->select('priorStateId','postStateId')
        ->leftJoin('workflowDefine','workflowDefine.id','=','workflowStateTransitions.workflowId')
        ->where('workflowDefine.contextId', 4)
        ->where('workflowStateTransitions.isDelete', false)->get();
        $states = [];
        
        foreach ($workflowStatesTransitions as $key => $value) {
            array_push($states, $value->priorStateId);
            array_push($states, $value->postStateId);
        }

        $allRelatedStates = array_values(array_unique($states));
        $pendingState = array_values(array_diff($allRelatedStates, $successAndFailiureStatus));

        return $pendingState;
    }


    /**
     * Following function cancel leave request.
     *
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"     
     */
    public function cancelShortLeaveRequest($id, $data)
    {
        try {
            DB::beginTransaction();

            $isAllowWf = ($data['isInInitialState']) ? false : true;
            $existingShortLeave = $this->store->getById($this->shortLeaveRequestModel, $id);
            if (is_null($existingShortLeave)) {
                DB::rollback();
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingShortLeave = (array) $existingShortLeave;
            $employeeId = $existingShortLeave['employeeId'];
            $leaveDates = [$existingShortLeave['date']];

            $isFullyCanceled = true;

            //save cancel leave request
            $cancelShortLeaveRequestData = [
                'shortLeaveRequestId' => $id,
                'employeeId' => $employeeId,
                'cancelReason' => $data['leaveCancelReason'],
                'currentState' => 1
            ];
            $newCancelLeave = $this->store->insert($this->cancelShortLeaveRequestModel, $cancelShortLeaveRequestData, true);

            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);

            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            if ($isAllowWf) {
                //need to handel workflow

                //check whether has pending or approved cancel leave requests for perticular leave
                $hasPreviousCancelRecord = $this->checkHasPendingOrApprovedCancelShortLeaveRequests($existingShortLeave);

                if ($hasPreviousCancelRecord) {
                    DB::rollBack();
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_HAS_PREVIOUS_CANCEL_REQUEST'), []);
                }


                $shortLeaveCancelDataSet = (array) $this->store->getById($this->cancelShortLeaveRequestModel, $newCancelLeave['id']);
                if (is_null($shortLeaveCancelDataSet)) {
                    DB::rollBack();
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $id);
                }

                $shortLeaveCancelDataSet['date'] = $existingShortLeave['date'];
                $shortLeaveCancelDataSet['fromTime'] = $existingShortLeave['fromTime'];
                $shortLeaveCancelDataSet['toTime'] = $existingShortLeave['toTime'];

                // this is the workflow context id related for Cancel Leave
                $context = 8;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $shortLeaveCancelDataSet, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                $cancelShortLeaveRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateLeaveRequest = $this->store->updateById($this->cancelShortLeaveRequestModel, $newCancelLeave['id'], $cancelShortLeaveRequstUpdated);
                if (!$updateLeaveRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $newCancelLeave['id']);
                }
            } else {
                $instanceLevelSequence = 0;
                $this->runShortLeaveCancellationProcess($existingShortLeave,  $isFullyCanceled, $newCancelLeave['id'], $instanceLevelSequence, $leaveDates);            
            }

            DB::commit();
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_CANCEL'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_CANCEL'), null);
        }
    }


    public function cancelAdminAssignShortLeaveRequest($id) {
        try {
            DB::beginTransaction();
            $existingLeave = $this->store->getById($this->shortLeaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave; 
            $employeeId = $existingLeave['employeeId'];

            $leaveDateDetails = DB::table('leaveRequestDetail')->where('leaveRequestDetail.leaveRequestId', $id)->get();

            $leaveDates[] = $existingLeave['date'];

            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);
            
            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }
            
            //change the leave request current state as cancelled
            $leaveRequestUpdated['currentState'] = 4;
            $updateCurrentState = $this->store->updateById($this->shortLeaveRequestModel, $existingLeave['id'], $leaveRequestUpdated);
           
            $dataSet = [
                'employeeId' => $employeeId,
                'dates' => $leaveDates
            ];

            event(new AttendanceDateDetailChangedEvent($dataSet));
            DB::commit();

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_CANCEL'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_CANCEL'), null);
        }
    }

    /**
     * Following function run the leave cancellation process
     */
    public function runShortLeaveCancellationProcess($existingShortLeave, $isFullyCanceled, $cancelLeaveRequestId, $instanceLevelSequence, $leaveDates)
    {
        $employeeId = $existingShortLeave['employeeId'];
        $id = $existingShortLeave['id'];
        $relateUser =  DB::table('user')->where('employeeId', $employeeId)->where('isDelete', false)->first();

        // if ($data['isInInitialState']) {
        if ($isFullyCanceled) {

            //change the leave request current state as cancelled
            $leaveRequestUpdated['currentState'] = 4;
            $updateCurrentState = $this->store->updateById($this->shortLeaveRequestModel, $existingShortLeave['id'], $leaveRequestUpdated);

            if (!empty($existingShortLeave['workflowInstanceId'])) {
                //change workflow instance prior state to cancel
                $workflowInstanceUpdated['currentStateId'] = 4;
                $updatePriorState = $this->store->updateById($this->workflowInstanceModel, $existingShortLeave['workflowInstanceId'], $workflowInstanceUpdated);


                // get instance relate approval level
                $instanceAprovalLevelData = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $existingShortLeave['workflowInstanceId'])->where('levelSequence', $instanceLevelSequence)->first();
                $instanceAprovalLevelData = (array) $instanceAprovalLevelData;

                //add new record to instance approval level detail table
                $instanceApprovalLevelDetailRecord = [
                    'workflowInstanceApproverLevelId' => $instanceAprovalLevelData['id'],
                    'performAction' => 4,
                    'approverComment' => null,
                    'performBy' => $relateUser->id,
                    'performAt' => Carbon::now()->toDateTimeString()
                ];

                $wfInstanceApprovalLevelDetailIdForCreate = DB::table('workflowInstanceApprovalLevelDetail')->insertGetId($instanceApprovalLevelDetailRecord);

                //update instance level data
                $insanceLevelDataset = [
                    'levelStatus' => 'CANCELED',
                    'isLevelCompleted' => false
                ];
                $updateInstanceLevelDataset = DB::table('workflowInstanceApprovalLevel')->where('id', '=', $instanceAprovalLevelData['id'])->update($insanceLevelDataset);
            }
        } 

        //update cancel leave request state and cancel leave request detail records status
        $cancelLeaveRequestUpdated['currentState'] = 2;
        $cancelLeaveRequestUpdated['approvedBy'] = $relateUser->id;
        $cancelLeaveRequestUpdated['approvedAt'] = Carbon::now()->toDateTimeString();
        $updatePriorState = $this->store->updateById($this->cancelShortLeaveRequestModel, $cancelLeaveRequestId, $cancelLeaveRequestUpdated);

        // $cancelLeaveRequestDetailUpdated['status'] = 'APPROVED';
        // $updatePriorState = DB::table('cancelLeaveRequestDetail')->whereIn('cancelLeaveRequestDetail.id', $leaveCancelRequestDetailIds)->update(['status' => 'APPROVED']);

        $dataSet = [
            'employeeId' => $employeeId,
            'dates' => $leaveDates
        ];

        event(new AttendanceDateDetailChangedEvent($dataSet));
    }

    /**
     * Following function check whether the leave request is fully approved leave request
     * Sample output: boolean
     */
    private function checkShortLeaveIsFullyApprovedOne($shortLeaveRequstData)
    {
        $shortLeaveRequstData = (array) $shortLeaveRequstData;
        if (!empty($shortLeaveRequstData['workflowInstanceId'])) {

            //get all cancel leave request that go through workflow that realte to leave request id
            $approvedLeaveRequestData = DB::table('workflowInstance')
                ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                ->where('workflowInstance.id', '=', $shortLeaveRequstData['workflowInstanceId'])
                ->where('workflowInstance.currentStateId', '=', 2)
                ->get();

            if (sizeof($approvedLeaveRequestData) == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }


    /**
     * Following function check whether the leave request has pending or approved leave cancel requests     *
     * Sample output: boolean
     */
    private function checkHasPendingOrApprovedCancelShortLeaveRequests($shortLeaveRequstData)
    {
        $shortLeaveRequstData = (array) $shortLeaveRequstData;
        //get all cancel leave request that go through workflow that realte to leave request id
        $cancelRequestData = DB::table('cancelShortLeaveRequest')
            ->where('cancelShortLeaveRequest.shortLeaveRequestId', '=', $shortLeaveRequstData['id'])
            ->whereNotNull('workflowInstanceId')
            ->get();


        if (!is_null($cancelRequestData) && sizeof($cancelRequestData) > 0) {
            $hasPreviousCancelRequest = false;
            foreach ($cancelRequestData as $key => $value) {
                $value = (array) $value;
                $pendngCancelRequestData = DB::table('workflowInstance')
                    ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                    ->where('workflowInstance.id', '=', $value['workflowInstanceId'])
                    ->whereIn('workflowInstance.currentStateId', [1,2])
                    ->get();

                if (sizeof($pendngCancelRequestData) > 0) {
                    $hasPreviousCancelRequest = true;
                    break;
                }
            }

            return $hasPreviousCancelRequest;
        } else {
            return false;
        }
    }

}
