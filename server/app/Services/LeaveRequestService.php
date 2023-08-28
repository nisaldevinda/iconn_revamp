<?php

namespace App\Services;

use Log;
use Exception;
use DateTime;
use DateInterval;
use DatePeriod;
use date;
use App\Library\Store;
use App\Library\Util;
use App\Library\Session;
use Illuminate\Support\Facades\Lang;
use App\Exports\LeaveRequestExcelExport;
use App\Jobs\EmailNotificationJob;
use App\Exports\LeaveSummaryReportExcelExport;
use App\Library\Email;
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
use App\Exports\LeaveEntitlementReportExcelExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Events\AttendanceDateDetailChangedEvent;
use App\Traits\AttendanceProcess;


/**
 * Name: LeaveRequestService
 * Purpose: Performs tasks related to the LeaveRequest model.
 * Description: Leave Service class is called by the LeaveRequestController where the requests related
 * to User LeaveRequest Model (CRUD operations and others).
 * Module Creator: Tharindu Darshana
 */
class LeaveRequestService extends BaseService
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
    private $leaveRequestModel;
    private $cancelLeaveRequestModel;
    private $leaveRequestDetailModel;
    private $cancelLeaveRequestDetailModel;
    private $leaveCoveringPersonRequestModel;
    private $leaveRequestEntitlementModel;

    private $workflowService;
    private $workflowInstanceDetailModel;
    private $orgEntityModel;
    private $workCalendarService;
    private $leaveTypeService;
    private $employeeModel;

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
        $this->employeeModel = $this->getModel('employee', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->workflowInstanceDetailModel= $this->getModel('workflowInstanceDetail', true);
        $this->leaveRequestModel = $this->getModel('leaveRequest', true);
        $this->orgEntityModel =  $this->getModel('orgEntity', true);
        $this->cancelLeaveRequestModel = $this->getModel('cancelLeaveRequest', true);
        $this->leaveRequestDetailModel = $this->getModel('leaveRequestDetail', true);
        $this->cancelLeaveRequestDetailModel = $this->getModel('cancelLeaveRequestDetail', true);
        $this->leaveCoveringPersonRequestModel = $this->getModel('leaveCoveringPersonRequest', true);
        $this->leaveRequestEntitlementModel = $this->getModel('leaveRequestEntitlement', true);
    }

    /**
     * Following function creates a leave. The leave details that are provided in the Request
     * are extracted and saved to the leave table in the database. leave_type_id is auto genarated 
     *
     * @param $leaveType array containing the leave type data
     * @return int | String | array
     *
     * Usage:
     * $leaveType => [
     *
     * ]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Leave Type created successfully!",
     * $data => {"title": "LK HR", ...} //$data has a similar set of values as the input
     *  */

    public function createLeave($leave, $requestType = null)
    {
        try {
            DB::beginTransaction();
            $isAllowWf = true;
            unset($leave['isGoThroughWf']);
            
            $employeeId ='';
            if (isset($leave['employeeId'])) {
                $employeeId = $leave['employeeId'];
            } else {
                $employeeId = $this->session->getUser()->employeeId;
            }
          
            $leave['employeeId'] = $employeeId;
            $manageCoveringPerson = isset($leave['manageCoveringPerson']) ? $leave['manageCoveringPerson'] : null;
            $leave['workflowInstanceId'] = null;
            $validationResponse = ModelValidator::validate($this->leaveRequestModel, $leave);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $validationResponse);
            } 

            $employeeCalendar = $this->workCalendarService->getEmployeeCalendar($employeeId);
            $leaveType = $this->leaveTypeService->getLeaveType($leave['leaveTypeId'])['data'];
            $leaveType->workingDayIds = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
                ->where('leaveTypeId', $leave['leaveTypeId'])
                ->get();

            $startDate = new DateTime($leave['fromDate']);
            $interval = new DateInterval('P1D');
            $endDate = new DateTime($leave['toDate']);
            $endDate = $endDate->add($interval);
            $dateArr = [];
            $leaveRequestEntitlements = [];
            $period = new DatePeriod(
                $startDate,
                $interval,
                $endDate
            );

            $leaveDateWisePeriodTypesArr = $this->getLeaveDateWisePeriodType($leave, $period);

            if ($manageCoveringPerson) {
                //check if leave request exist for the selected date 
                foreach ($period as $key => $value) {
                    $datVal = $value->format('Y-m-d');
                    $coveringPersonLeaveRequests =  DB::table('leaveRequestDetail')
                    ->leftJoin('leaveRequest','leaveRequest.id',"=","leaveRequestDetail.leaveRequestId")
                    ->where('leaveRequestDetail.leaveDate', '=', $datVal)
                    ->where('leaveRequest.employeeId','=',$leave['selectedCoveringPerson'])
                    ->where(function($query)
                        {
                            $query->where('leaveRequestDetail.status','APPROVED');
                            $query->orwhere('leaveRequestDetail.status','PENDING');
                        })
                    ->pluck('leavePeriodType')->toArray();
                    
                    $checkleaveExistsForCoveringPerson = false;

                    if ($leaveDateWisePeriodTypesArr[$datVal] === 'FULL_DAY') {
                        if  ( in_array('FIRST_HALF_DAY' , $coveringPersonLeaveRequests) || in_array('SECOND_HALF_DAY' , $coveringPersonLeaveRequests) || (in_array('FIRST_HALF_DAY' , $coveringPersonLeaveRequests) && in_array('SECOND_HALF_DAY' , $coveringPersonLeaveRequests)) || in_array('FULL_DAY' , $coveringPersonLeaveRequests) || in_array('OUT_SHORT_LEAVE' , $coveringPersonLeaveRequests) || in_array('IN_SHORT_LEAVE' , $coveringPersonLeaveRequests)) {
                        $checkleaveExistsForCoveringPerson = true;
                        }
                    }
                    if ($leaveDateWisePeriodTypesArr[$datVal] === 'FIRST_HALF_DAY') {
                        if (in_array('FIRST_HALF_DAY' , $coveringPersonLeaveRequests) || in_array('FULL_DAY' , $coveringPersonLeaveRequests) ||  in_array('IN_SHORT_LEAVE' , $coveringPersonLeaveRequests)) {
                            $checkleaveExistsForCoveringPerson = true;
                        }
                    }
                    if ($leaveDateWisePeriodTypesArr[$datVal] === 'SECOND_HALF_DAY') {
                        if (in_array('SECOND_HALF_DAY' , $coveringPersonLeaveRequests) || in_array('FULL_DAY' , $coveringPersonLeaveRequests) || in_array('OUT_SHORT_LEAVE' , $coveringPersonLeaveRequests)) {
                            $checkleaveExistsForCoveringPerson = true;
                        }
                    }

                    if ($leaveDateWisePeriodTypesArr[$datVal] === 'IN_SHORT_LEAVE') {
                        if  ( in_array('FIRST_HALF_DAY' , $coveringPersonLeaveRequests) || in_array('FULL_DAY' , $coveringPersonLeaveRequests) || in_array('IN_SHORT_LEAVE' , $coveringPersonLeaveRequests)) {
                            $checkleaveExistsForCoveringPerson = true;
                        }
                    }

                    if ($leaveDateWisePeriodTypesArr[$datVal] === 'OUT_SHORT_LEAVE') {
                        if  (in_array('SECOND_HALF_DAY' , $coveringPersonLeaveRequests) || in_array('FULL_DAY' , $coveringPersonLeaveRequests) || in_array('OUT_SHORT_LEAVE' , $coveringPersonLeaveRequests) ) {
                            $checkleaveExistsForCoveringPerson = true;
                        }
                    }

                    if ($checkleaveExistsForCoveringPerson) {
                    return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_COVERINGPERSON_LEAVE_REQUEST_EXISTS'), null);
                    }
                    
                }
            }

            //check if leave request exist for the selected date 
            foreach ($period as $key => $value) {
                $datVal = $value->format('Y-m-d');
                $leaveRequests =  DB::table('leaveRequestDetail')
                   ->leftJoin('leaveRequest','leaveRequest.id',"=","leaveRequestDetail.leaveRequestId")
                   ->where('leaveRequestDetail.leaveDate', '=', $datVal)
                   ->where('leaveRequest.employeeId','=',$leave['employeeId'])
                   ->where(function($query)
                    {
                        $query->where('leaveRequestDetail.status','APPROVED');
                        $query->orwhere('leaveRequestDetail.status','PENDING');
                    })
                   ->pluck('leavePeriodType')->toArray();
                 
                $checkleaveExists = false;

                if ($leaveDateWisePeriodTypesArr[$datVal] === 'FULL_DAY') {
                    if  ( in_array('FIRST_HALF_DAY' , $leaveRequests) || in_array('SECOND_HALF_DAY' , $leaveRequests) || (in_array('FIRST_HALF_DAY' , $leaveRequests) && in_array('SECOND_HALF_DAY' , $leaveRequests)) || in_array('FULL_DAY' , $leaveRequests) || in_array('OUT_SHORT_LEAVE' , $leaveRequests) || in_array('IN_SHORT_LEAVE' , $leaveRequests)) {
                       $checkleaveExists = true;
                    }
                }
                if ($leaveDateWisePeriodTypesArr[$datVal] === 'FIRST_HALF_DAY') {
                    if (in_array('FIRST_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) ||  in_array('IN_SHORT_LEAVE' , $leaveRequests)) {
                        $checkleaveExists = true;
                    }
                }
                if ($leaveDateWisePeriodTypesArr[$datVal] === 'SECOND_HALF_DAY') {
                    if (in_array('SECOND_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('OUT_SHORT_LEAVE' , $leaveRequests)) {
                        $checkleaveExists = true;
                    }
                }

                if ($leaveDateWisePeriodTypesArr[$datVal] === 'IN_SHORT_LEAVE') {
                    if  ( in_array('FIRST_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('IN_SHORT_LEAVE' , $leaveRequests)) {
                        $checkleaveExists = true;
                     }
                }

                if ($leaveDateWisePeriodTypesArr[$datVal] === 'OUT_SHORT_LEAVE') {
                    if  (in_array('SECOND_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('OUT_SHORT_LEAVE' , $leaveRequests) ) {
                        $checkleaveExists = true;
                     }
                }

                if ($checkleaveExists) {
                   return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_CREATE_LEAVE_REQUEST_EXISTS'), null);
                }
                
            }

            $newLeave = $this->store->insert($this->leaveRequestModel, $leave, true);

            foreach ($period as $key => $value) {
                $datVal = $value->format('Y-m-d');
                $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $value);

                if (!empty($shift)) {
                    $entitlePortion = 0;
                    $validLeaveDay = false;

                    if ( // if full day leaves
                        $shift->noOfDay > 0
                        && $leaveDateWisePeriodTypesArr[$datVal] == 'FULL_DAY'
                    ) {
                        $validLeaveDay = true;
                        $entitlePortion = $shift->noOfDay;
                    } else if ( // if half day leaves
                        $shift->noOfDay >= 0.5
                        && ($leaveDateWisePeriodTypesArr[$datVal] == 'FIRST_HALF_DAY' || $leaveDateWisePeriodTypesArr[$datVal] == 'SECOND_HALF_DAY')
                    ) {
                        $validLeaveDay = true;
                        $entitlePortion = 0.5;
                    } else if ( // if short leave
                        $shift->noOfDay >= 0
                        && ($leaveDateWisePeriodTypesArr[$datVal] == 'IN_SHORT_LEAVE' || $leaveDateWisePeriodTypesArr[$datVal] == 'OUT_SHORT_LEAVE')
                    ) {
                        $validLeaveDay = true;
                        $entitlePortion = 1.00;
                    }

                    if ($validLeaveDay) {
                        $dateArr[] = $datVal;

                        $leaveRequestEntitlement = $this->leaveEntitlementAllocation(
                            $employeeId,
                            $leaveType,
                            $value,
                            $entitlePortion,
                            $leaveRequestEntitlements
                        );

                        if (empty($leaveRequestEntitlement)) {
                            DB::rollback();
                            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_NOT_ENOUGH_ENTITLEMENT'), null);
                        }

                        $leaveRequestEntitlements = array_merge($leaveRequestEntitlements, $leaveRequestEntitlement);
                    }
                }
            }

            if (empty($dateArr)) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_NO_WORKING_DAYS'), null);
            }
            $leaveDates = [];

            // handle maximum consecutive days
            if (!empty($leaveType->maximumConsecutiveLeaveDays) && $leaveType->maximumConsecutiveLeaveDays > 0) {
                $exceededMaxConsecutiveCount = false;

                if (count($dateArr) > $leaveType->maximumConsecutiveLeaveDays) {
                    $exceededMaxConsecutiveCount = true;
                } else {
                    $outerConsectiveCount = $leaveType->maximumConsecutiveLeaveDays - count($dateArr) + 1;

                    $breakLowerSequence = false;
                    $breakUpperSequence = false;
                    $lowerDate = $dateArr[0];
                    $upperDate = end($dateArr);
                    $lowerLimit = 5;
                    $upperLimit = 5;

                    while ($outerConsectiveCount > 0) {
                        if (!$breakLowerSequence) {
                            $lowerDateObj = new DateTime($lowerDate);
                            $nextLowerDateObj = $lowerDateObj->sub(new DateInterval('P1D'));
                            $nextLowerDate = $nextLowerDateObj->format('Y-m-d');

                            $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $nextLowerDateObj);
                            if (empty($shift)) {
                                $lowerDate = $nextLowerDate;
                                $lowerLimit -= 1;
                                if ($lowerLimit <= 0) {
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            $nextLowerDateLeaveRequest = DB::table('leaveRequestDetail')
                                ->leftJoin('leaveRequest','leaveRequest.id',"=","leaveRequestDetail.leaveRequestId")
                                ->where('leaveRequestDetail.leaveDate', '=', $nextLowerDate)
                                ->where('leaveRequest.employeeId', '=', $leave['employeeId'])
                                ->where('leaveRequest.leaveTypeId', '=', $leave['leaveTypeId'])
                                ->where('leaveRequestDetail.leavePeriodType', '=', 'FULL_DAY')
                                ->where(function($query) {
                                    $query->where('leaveRequestDetail.status','APPROVED');
                                    $query->orwhere('leaveRequestDetail.status','PENDING');
                                })
                                ->pluck('leaveRequestDetail.id')->toArray();

                            if (!empty($nextLowerDateLeaveRequest)) {
                                $lowerDate = $nextLowerDate;
                                $outerConsectiveCount--;
                            } else {
                                $breakLowerSequence = true;
                            }
                        } else if (!$breakUpperSequence) {
                            $upperDateObj = new DateTime($upperDate);
                            $nextUpperDateObj = $upperDateObj->add(new DateInterval('P1D'));
                            $nextUpperDate = $nextUpperDateObj->format('Y-m-d');

                            $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $nextUpperDateObj);
                            if (empty($shift)) {
                                $upperDate = $nextUpperDate;
                                $upperLimit -= 1;
                                if ($upperLimit <= 0) {
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            $nextUpperDateLeaveRequest = DB::table('leaveRequestDetail')
                                ->leftJoin('leaveRequest','leaveRequest.id',"=","leaveRequestDetail.leaveRequestId")
                                ->where('leaveRequestDetail.leaveDate', '=', $nextUpperDate)
                                ->where('leaveRequest.employeeId', '=', $leave['employeeId'])
                                ->where('leaveRequest.leaveTypeId', '=', $leave['leaveTypeId'])
                                ->where('leaveRequestDetail.leavePeriodType', '=', 'FULL_DAY')
                                ->where(function($query) {
                                    $query->where('leaveRequestDetail.status','APPROVED');
                                    $query->orwhere('leaveRequestDetail.status','PENDING');
                                })
                                ->pluck('leaveRequestDetail.id')->toArray();

                            if (!empty($nextUpperDateLeaveRequest)) {
                                $upperDate = $nextUpperDate;
                                $outerConsectiveCount--;
                            } else {
                                $breakUpperSequence = true;
                            }
                        } else {
                            break;
                        }
                    }

                    if ($outerConsectiveCount <= 0) {
                        $exceededMaxConsecutiveCount = true;
                    }
                }

                if ($exceededMaxConsecutiveCount) {
                    $dayCount ='';
                    if ($leaveType->maximumConsecutiveLeaveDays <= 1) {
                        $dayCount =$leaveType->maximumConsecutiveLeaveDays .' day';
                    } else {
                        $dayCount =$leaveType->maximumConsecutiveLeaveDays .' days';
                    }
                    $message = "Not allowed to apply for more than ".$dayCount ." consecutively";
                    return $this->error(500, Lang::get($message), null);
                }
            }

            foreach ($dateArr as $dateKey => $date) {
                $leaveDetailData = [
                    'leaveRequestId' => $newLeave['id'],
                    'leaveDate' => $date, 
                    'status' => ($isAllowWf) ? 'PENDING' : 'APPROVED',
                    'leavePeriodType' => $leaveDateWisePeriodTypesArr[$date]
                ];

                $leaveDetailSave = $this->store->insert($this->leaveRequestDetailModel, $leaveDetailData, true);
                $leaveDates[] = $date;

                $dateRelatedEntitlements = array_filter($leaveRequestEntitlements, function ($entitlement) use ($date) {
                    return $entitlement['date']->format('Y-m-d') == $date;
                });

                foreach ($dateRelatedEntitlements as $dateRelatedEntitlement) {
                    unset($dateRelatedEntitlement['date']);
                    $dateRelatedEntitlement['leaveRequestDetailId'] = $leaveDetailSave['id'];

                    $leaveRequestEntitlementResponse = $this->store->insert($this->leaveRequestEntitlementModel, $dateRelatedEntitlement, true);
                }
            }

            //check whether self service reuest type is lock locked
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

            $pendingCountUpdates = [];
            foreach ($leaveRequestEntitlements as $entitlement) {
                if (empty($pendingCountUpdates[$entitlement['leaveEntitlementId']])) {
                    $pendingCountUpdates[$entitlement['leaveEntitlementId']] = $entitlement['entitlePortion'];
                } else {
                    $pendingCountUpdates[$entitlement['leaveEntitlementId']] += $entitlement['entitlePortion'];
                }
            }

            $numLeaveDates = 0;
            foreach ($pendingCountUpdates as $id => $pendingCountUpdate) {
                $numLeaveDates += $pendingCountUpdate;
                $pendingCountUpdateResponse = $this->store->getFacade()::table('leaveEntitlement')
                    ->where('id', $id)
                    ->increment('pendingCount', $pendingCountUpdate);
            }


            //update num of leave dates
            $leaveRequstUpdatedData['numberOfLeaveDates'] = $numLeaveDates;
            $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $newLeave['id'], $leaveRequstUpdatedData);

            // save attachment and update the leave Request Record
            if (sizeof($leave['attachmentList']) > 0) {
                $attachmentIds = [];
                foreach ($leave['attachmentList'] as $key2 => $attachmentData) {
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
                $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $newLeave['id'], $leaveRequstUpdated);

            }

            if ($isAllowWf && !$manageCoveringPerson) {
                $leaveDataSet = (array) $this->store->getById($this->leaveRequestModel, $newLeave['id']);
                if (is_null($leaveDataSet)) {
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $id);
                }
                // this is the workflow context id related for Apply Leave
                $context = 2;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }
                
                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $leaveDataSet, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }
               
                $leaveRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $newLeave['id'], $leaveRequstUpdated);
                if (!$updateLeaveRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'),$newLeave['id']);
                }                
            }

            if ($manageCoveringPerson) {
                //add covering person request
                $coveringRequestData = [
                    'leaveRequestId' => $newLeave['id'],
                    'coveringEmployeeId' => $leave['selectedCoveringPerson'],
                    'state' => 'PENDING'
                ];

                $coveringPersonRequestSave = $this->store->insert($this->leaveCoveringPersonRequestModel, $coveringRequestData, true);

                $leaveUpdateData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($this->leaveRequestModel, $newLeave['id'], $leaveUpdateData);

                //get leave request employee detail
                $requestEmpData =  DB::table('employee')->select('workEmail','firstName','lastName')
                ->where('employee.id','=',$employeeId)->first();

                //get covering person employee detail
                $coveringEmpData =  DB::table('employee')->select('workEmail','firstName')
                ->where('employee.id','=',$leave['selectedCoveringPerson'])->first();

                $fromDate = $newLeave['fromDate'];
                $toDate = $newLeave['toDate'];
                $numOfLeaveDates = $numLeaveDates;
                $dayString = $numLeaveDates == 1 ? 'Day.' : 'Days.';
                $requestEmployeeName = $requestEmpData->firstName.' '.$requestEmpData->lastName;

                //set email body
                $emailBody = $requestEmployeeName." is assign you as the covering person of the leave that he/she suppose to get from ".$fromDate." to ". $toDate." for ". $numOfLeaveDates.' '.$dayString;

                //send email for covering person
                $newEmail =  dispatch(new EmailNotificationJob(new Email('emails.leaveCoveringPersonEmailContent', array($coveringEmpData->workEmail), "Leave Covering Request", array([]), array("receipientFirstName" => $coveringEmpData->firstName, "emailBody" => $emailBody))))->onQueue('email-queue');
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
            Log::error($e);
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function return leave date wise leave period type.
     *
     * @return | array
     *
     * Sample output:
     * [
     *      '2022-09-01' => 'FULL_DAY'
     * ]
     */
    private function getLeaveDateWisePeriodType($leave, $period)
    {
        $periodTypeArray = [];
        foreach ($period as $key => $value) {
            $datVal = $value->format('Y-m-d');
            if ($leave['fromDate'] === $datVal) {
                $periodTypeArray[$datVal] = $leave['fromDateLeavePeriodType'];
            } else if ($leave['toDate'] === $datVal) {
                $periodTypeArray[$datVal] = (!empty($leave['toDateleavePeriodType'])) ? $leave['toDateleavePeriodType'] : $leave['fromDateLeavePeriodType'];
            } else {
                $periodTypeArray[$datVal] = 'FULL_DAY';
            }
        }

        return $periodTypeArray;
    }


    /**
     * Following function retrives all leaves.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All leaves retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllLeaves($permittedFields, $options)
    {
        try {
            $filteredLeaves = $this->store->getAll(
                $this->leaveRequestModel,
                $permittedFields,
                $options, 
                [],
                []);
          
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GETALL'), $filteredLeaves);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single leave for a provided leave_type_id.
     *
     * @param $id user leave id
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
    public function getLeave($id)
    {
        try {
            $leave = $this->store->getById($this->leaveRequestModel, $id);
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
     * Following function updates a leave.
     *
     * @param $id leave id
     * @param $leave array containing leave data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateLeave($id, $leave)
    {
        try {
            $validationResponse = ModelValidator::validate($this->leaveRequestModel, $leave, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            
            $result = $this->store->updateById($this->leaveRequestModel, $id, $leave);

            if (!$result) {
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_UPDATE'), $leave);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a leave.
     *
     * @param $id leave id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteLeave($id)
    {
        try {
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            
            $recordExist = Util::checkRecordsExist($this->leaveRequestModel,$id);
            
            if (!empty($recordExist) ) {
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_NOTALLOWED'), null);
            } 
            $result = $this->store->deleteById($this->leaveRequestModel, $id, false);

            if ($result==0) {
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_DELETE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_DELETE'), null);
        }
    }

    public function assignLeave($leave) {
        try {
            DB::beginTransaction();

            $employeeId = $leave['employeeId'];
            $leave['workflowInstanceId'] = null;
            $validationResponse = ModelValidator::validate($this->leaveRequestModel, $leave);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $validationResponse);
            } 

            $employeeCalendar = $this->workCalendarService->getEmployeeCalendar($employeeId);
            $leaveType = $this->leaveTypeService->getLeaveType($leave['leaveTypeId'])['data'];
            $leaveType->workingDayIds = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
                ->where('leaveTypeId', $leave['leaveTypeId'])
                ->get();
            $leave['currentState'] = 2;

            // Todo :  Need to validate with that employee can applly leave with remain leave type balance 
            $newLeave = $this->store->insert($this->leaveRequestModel, $leave, true);

            $startDate = new DateTime($leave['fromDate']);
            $interval = new DateInterval('P1D');
            $endDate = new DateTime($leave['toDate']);
            $endDate = $endDate->add($interval);
            $dateArr = [];
            $leaveRequestEntitlements = [];
            $period = new DatePeriod(
                $startDate,
                $interval,
                $endDate
            );

            $leaveDateWisePeriodTypesArr = $this->getLeaveDateWisePeriodType($leave, $period);

            //check if leave request exist for the selected date 
            foreach ($period as $key => $value) {
                $datVal = $value->format('Y-m-d');
                $leaveRequests =  DB::table('leaveRequestDetail')
                   ->leftJoin('leaveRequest','leaveRequest.id',"=","leaveRequestDetail.leaveRequestId")
                   ->where('leaveRequestDetail.leaveDate', '=', $datVal)
                   ->where('leaveRequest.employeeId','=',$leave['employeeId'])
                   ->where(function($query)
                    {
                        $query->where('leaveRequestDetail.status','APPROVED');
                        $query->orwhere('leaveRequestDetail.status','PENDING');
                    })
                   ->pluck('leavePeriodType')->toArray();
                 
                $checkleaveExists = false;
                
                if ($leaveDateWisePeriodTypesArr[$datVal] === 'FULL_DAY') {
                    if  ( in_array('FIRST_HALF_DAY' , $leaveRequests) || in_array('SECOND_HALF_DAY' , $leaveRequests) || (in_array('FIRST_HALF_DAY' , $leaveRequests) && in_array('SECOND_HALF_DAY' , $leaveRequests) ) || in_array('FULL_DAY' , $leaveRequests) || in_array('OUT_SHORT_LEAVE' , $leaveRequests) || in_array('IN_SHORT_LEAVE' , $leaveRequests) ) {
                       $checkleaveExists = true;
                    }
                }
                if ($leaveDateWisePeriodTypesArr[$datVal] === 'FIRST_HALF_DAY') {
                    if (in_array('FIRST_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('IN_SHORT_LEAVE' , $leaveRequests)) {
                        $checkleaveExists = true;
                    }
                }
                if ($leaveDateWisePeriodTypesArr[$datVal] === 'SECOND_HALF_DAY') {
                    if (in_array('SECOND_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('OUT_SHORT_LEAVE' , $leaveRequests)) {
                        $checkleaveExists = true;
                    }
                }

                if ($leaveDateWisePeriodTypesArr[$datVal] === 'IN_SHORT_LEAVE') {
                    if  ( in_array('FIRST_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('IN_SHORT_LEAVE' , $leaveRequests) ) {
                        $checkleaveExists = true;
                     }
                }

                if ($leaveDateWisePeriodTypesArr[$datVal] === 'OUT_SHORT_LEAVE') {
                    if  ( in_array('SECOND_HALF_DAY' , $leaveRequests) || in_array('FULL_DAY' , $leaveRequests) || in_array('OUT_SHORT_LEAVE' , $leaveRequests) ) {
                        $checkleaveExists = true;
                     }
                }

                if ($checkleaveExists) {
                   return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_CREATE_LEAVE_REQUEST_EXISTS_FOR_ASSIGN_LEAVE'), null);
                }
            }

            foreach ($period as $key => $value) {
                $datVal = $value->format('Y-m-d');
                $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $value);

                if (!empty($shift)) {
                    $entitlePortion = 0;
                    $validLeaveDay = false;

                    if ( // if full day leaves
                        $shift->noOfDay > 0
                        && $leaveDateWisePeriodTypesArr[$datVal] == 'FULL_DAY'
                    ) {
                        $validLeaveDay = true;
                        $entitlePortion = $shift->noOfDay;
                    } else if ( // if half day leaves
                        $shift->noOfDay >= 0.5
                        && ($leaveDateWisePeriodTypesArr[$datVal] == 'FIRST_HALF_DAY' || $leaveDateWisePeriodTypesArr[$datVal] == 'SECOND_HALF_DAY')
                    ) {
                        $validLeaveDay = true;
                        $entitlePortion = 0.5;
                    } else if ( // if short leave
                        $shift->noOfDay >= 0
                        && ($leaveDateWisePeriodTypesArr[$datVal] == 'IN_SHORT_LEAVE' || $leaveDateWisePeriodTypesArr[$datVal] == 'OUT_SHORT_LEAVE')
                    ) {
                        $validLeaveDay = true;
                        $entitlePortion = 1.00;
                    }

                    if ($validLeaveDay) {
                        $dateArr[] = $datVal;

                        $leaveRequestEntitlement = $this->leaveEntitlementAllocation(
                            $employeeId,
                            $leaveType,
                            $value,
                            $entitlePortion,
                            $leaveRequestEntitlements
                        );

                        if (empty($leaveRequestEntitlement)) {
                            DB::rollback();
                            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_NOT_ENOUGH_ENTITLEMENT'), null);
                        }

                        $leaveRequestEntitlements = array_merge($leaveRequestEntitlements, $leaveRequestEntitlement);
                    }
                }
            }

            if (empty($dateArr)) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_NO_WORKING_DAYS'), null);
            }

            // handle maximum consecutive days
            if (!empty($leaveType->maximumConsecutiveLeaveDays) && $leaveType->maximumConsecutiveLeaveDays > 0) {
                $exceededMaxConsecutiveCount = false;

                if (count($dateArr) > $leaveType->maximumConsecutiveLeaveDays) {
                    $exceededMaxConsecutiveCount = true;
                } else {
                    $outerConsectiveCount = $leaveType->maximumConsecutiveLeaveDays - count($dateArr) + 1;

                    $breakLowerSequence = false;
                    $breakUpperSequence = false;
                    $lowerDate = $dateArr[0];
                    $upperDate = end($dateArr);
                    $lowerLimit = 5;
                    $upperLimit = 5;

                    while ($outerConsectiveCount > 0) {
                        if (!$breakLowerSequence) {
                            $lowerDateObj = new DateTime($lowerDate);
                            $nextLowerDateObj = $lowerDateObj->sub(new DateInterval('P1D'));
                            $nextLowerDate = $nextLowerDateObj->format('Y-m-d');

                            $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $nextLowerDateObj);
                            if (empty($shift)) {
                                $lowerDate = $nextLowerDate;
                                $lowerLimit -= 1;
                                if ($lowerLimit <= 0) {
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            $nextLowerDateLeaveRequest = DB::table('leaveRequestDetail')
                            ->leftJoin('leaveRequest', 'leaveRequest.id', "=", "leaveRequestDetail.leaveRequestId")
                            ->where('leaveRequestDetail.leaveDate', '=', $nextLowerDate)
                                ->where('leaveRequest.employeeId', '=', $leave['employeeId'])
                                ->where('leaveRequest.leaveTypeId', '=', $leave['leaveTypeId'])
                                ->where('leaveRequestDetail.leavePeriodType', '=', 'FULL_DAY')
                                ->where(function ($query) {
                                    $query->where('leaveRequestDetail.status', 'APPROVED');
                                    $query->orwhere('leaveRequestDetail.status', 'PENDING');
                                })
                                ->pluck('leaveRequestDetail.id')->toArray();

                            if (!empty($nextLowerDateLeaveRequest)) {
                                $lowerDate = $nextLowerDate;
                                $outerConsectiveCount--;
                            } else {
                                $breakLowerSequence = true;
                            }
                        } else if (!$breakUpperSequence) {
                            $upperDateObj = new DateTime($upperDate);
                            $nextUpperDateObj = $upperDateObj->add(new DateInterval('P1D'));
                            $nextUpperDate = $nextUpperDateObj->format('Y-m-d');

                            $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $nextUpperDateObj);
                            if (empty($shift)) {
                                $upperDate = $nextUpperDate;
                                $upperLimit -= 1;
                                if ($upperLimit <= 0) {
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            $nextUpperDateLeaveRequest = DB::table('leaveRequestDetail')
                            ->leftJoin('leaveRequest', 'leaveRequest.id', "=", "leaveRequestDetail.leaveRequestId")
                            ->where('leaveRequestDetail.leaveDate', '=', $nextUpperDate)
                                ->where('leaveRequest.employeeId', '=', $leave['employeeId'])
                                ->where('leaveRequest.leaveTypeId', '=', $leave['leaveTypeId'])
                                ->where('leaveRequestDetail.leavePeriodType', '=', 'FULL_DAY')
                                ->where(function ($query) {
                                    $query->where('leaveRequestDetail.status', 'APPROVED');
                                    $query->orwhere('leaveRequestDetail.status', 'PENDING');
                                })
                                ->pluck('leaveRequestDetail.id')->toArray();

                            if (!empty($nextUpperDateLeaveRequest)) {
                                $upperDate = $nextUpperDate;
                                $outerConsectiveCount--;
                            } else {
                                $breakUpperSequence = true;
                            }
                        } else {
                            break;
                        }
                    }

                    if ($outerConsectiveCount <= 0) {
                        $exceededMaxConsecutiveCount = true;
                    }
                }

                if ($exceededMaxConsecutiveCount) {
                    $dayCount = '';
                    if ($leaveType->maximumConsecutiveLeaveDays <= 1) {
                        $dayCount = $leaveType->maximumConsecutiveLeaveDays . ' day';
                    } else {
                        $dayCount = $leaveType->maximumConsecutiveLeaveDays . ' days';
                    }
                    $message = "Not allowed to assign for more than " . $dayCount . " consecutively";
                    return $this->error(500, Lang::get($message), null);
                }
            }

            $leaveDates = [];

            foreach ($dateArr as $dateKey => $date) {

                $leaveDetailData = [
                    'leaveRequestId' => $newLeave['id'],
                    'leaveDate' => $date, 
                    'status' => 'APPROVED',
                    'leavePeriodType' => $leaveDateWisePeriodTypesArr[$date]
                ];
                
                $leaveDetailSave = $this->store->insert($this->leaveRequestDetailModel, $leaveDetailData, true);
                $leaveDates[] = $date; 

                $dateRelatedEntitlements = array_filter($leaveRequestEntitlements, function ($entitlement) use ($date) {
                    return $entitlement['date']->format('Y-m-d') == $date;
                });

                foreach ($dateRelatedEntitlements as $dateRelatedEntitlement) {
                    unset($dateRelatedEntitlement['date']);
                    $dateRelatedEntitlement['leaveRequestDetailId'] = $leaveDetailSave['id'];

                    $leaveRequestEntitlementResponse = $this->store->insert($this->leaveRequestEntitlementModel, $dateRelatedEntitlement, true);
                }
            }

            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);
            
            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            $approvedCountUpdates = [];
            foreach ($leaveRequestEntitlements as $entitlement) {
                if (empty($approvedCountUpdates[$entitlement['leaveEntitlementId']])) {
                    $approvedCountUpdates[$entitlement['leaveEntitlementId']] = $entitlement['entitlePortion'];
                } else {
                    $approvedCountUpdates[$entitlement['leaveEntitlementId']] += $entitlement['entitlePortion'];
                }
            }

            $numLeaveDates = 0;
            foreach ($approvedCountUpdates as $id => $approvedCountUpdate) {
                $numLeaveDates += $approvedCountUpdate;
                $approvedCountUpdateResponse = $this->store->getFacade()::table('leaveEntitlement')
                    ->where('id', $id)
                    ->increment('usedCount', $approvedCountUpdate);
            }

            if ($leave['leavePeriodType'] == 'IN_SHORT_LEAVE' || $leave['leavePeriodType'] == 'OUT_SHORT_LEAVE') {
                $numLeaveDates = null;
            }

            //update num of leave dates
            $leaveRequstUpdatedData['numberOfLeaveDates'] = $numLeaveDates;
            $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $newLeave['id'], $leaveRequstUpdatedData);

            // save attachment and update the leave Request Record
            if (sizeof($leave['attachmentList']) > 0) {
                $attachmentIds = [];
                foreach ($leave['attachmentList'] as $key2 => $attachmentData) {
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
                $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $newLeave['id'], $leaveRequstUpdated);
                
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



    public function getLeaveAttachments($id) {
        try {
            //get realted leave
            $leaveDataSet = (array) $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($leaveDataSet)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $id);
            }

            $leaveDataSet = (array) $leaveDataSet;
            $fileAttchements = json_decode($leaveDataSet['fileAttachementIds']);
            $attachments = DB::table('fileStoreObject')
                    ->select('*')
                    ->whereIn('id', $fileAttchements)
                    ->get();


            foreach ($attachments as $key => $value) {
                $value = (array) $value;
                $base64 = $file = $this->fileStorage->getBase64EncodedObject($value['id']);
                $attachments[$key]->data = $base64->data;

            }
        
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_CREATE'), $attachments);
        } catch (Exception $e) {
            DB::rollback();
            error_log($e->getMessage());
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), null);
        }
    }

    public function getManagerLeaveRequestData($request)
    {
        try {
            $employeeId = $request->query('employee', null);
            $location = $request->query('location', null);
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $leaveType = $request->query('leaveType', null);
            $department = $request->query('department', null);
            $status = json_decode($request->query('status', null));
            $accessType = 'manager';
            $isWithInactiveEmployees = $request->query('isWithInactiveEmployees', null);
            $filter = $request->query('filter', null);
            $filter = json_decode($filter);
            $leaveType = (!empty($leaveType)) ? json_decode($leaveType) : [];

            if (empty($employeeId)) {
                $employeeId = $this->session->getContext()->getManagerPermittedEmployeeIds();
            }

            $leaveRequests = $this->getLeaveRequestData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $location, $accessType, $department,$isWithInactiveEmployees, $filter);
            // $leaveRequests = [];
            
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getEmployeeLeaveRequestData($request)
    {
        try {
            $employeeId = $this->session->getEmployee()->id;
            
            if (!$employeeId) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_GET'));
            }
            
            $fromDate = $request->query('fromDate', null);
            $location = $request->query('location', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $leaveType = json_decode($request->query('leaveType', null));
            $status = json_decode($request->query('status', null));
            $accessType = 'employee';
            $department = $request->query('department', null);
            $isWithInactiveEmployees = $request->query('isWithInactiveEmployees', null);
            $filter = $request->query('filter', null);
            $filter = json_decode($filter);
            
            $leaveRequests = $this->getLeaveRequestData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $location, $accessType, $department, $isWithInactiveEmployees, $filter);
            // $leaveRequests = [];
            
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getAdminLeaveRequestData($request)
    {
        try {
            $employeeId = $request->query('employee', null);
            $location = $request->query('location', null);
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $leaveType = json_decode($request->query('leaveType', null));
            $status = json_decode($request->query('status', null));
            $requestedUser = $this->session->getUser();
            $roleId = $requestedUser->adminRoleId;
            $userRole   = $this->redis->getUserRole($roleId);
            $scopeOfAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $scopeOfAccess = (array) $scopeOfAccess;
            $accessType = 'admin';
            $department = $request->query('department', null);
            $isWithInactiveEmployees = $request->query('isWithInactiveEmployees', null);
            $filter = $request->query('filter', null);
            $filter = json_decode($filter);


            if (empty($employeeId)) {
                $employeeId = $this->session->getContext()->getAdminPermittedEmployeeIds();
            }

            if (empty($location)) {
               $location = (!empty($scopeOfAccess['location'])) ? $scopeOfAccess['location'] : [];
            }
        
            $leaveRequests = $this->getLeaveRequestData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $location, $accessType, $department, $isWithInactiveEmployees, $filter);

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    private function getAllLeaveRelateSuccessStates() {
        $workflows = DB::table('workflowDefine')->select('sucessStates')->where('contextId', 2)->get();
        $states = [];
        foreach ($workflows as $key => $value) {
            $sucessStatesArr = json_decode($value->sucessStates);
            $states =  array_merge($states, $sucessStatesArr);
        }

        return array_unique($states);
    }

    private function getAllLeaveRelateFaliureStates() {
        $workflows = DB::table('workflowDefine')->select('failureStates')->where('contextId', 2)->get();
        $states = [];
        foreach ($workflows as $key => $value) {
            $sucessStatesArr = json_decode($value->failureStates);
            $states =  array_merge($states, $sucessStatesArr);
        }

        return array_unique($states);
    }

    private function getAllLeaveRelatePendingStates($successAndFailiureStatus) {
        $workflowStatesTransitions = DB::table('workflowStateTransitions')->select('priorStateId','postStateId')
        ->leftJoin('workflowDefine','workflowDefine.id','=','workflowStateTransitions.workflowId')
        ->where('workflowDefine.contextId', 2)
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

    private function getLeaveRequestData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $location, $accessType, $department, $isWithInactiveEmployees, $filter)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $empIdArrString= "";
            $locIdArrString= "";
            $filterFieldArr = [];
            $statusIds = null;
            
            if (!empty($fromDate)) {
                $fromDate = strtotime($fromDate);
                $fromDate = date('Y-m-d', $fromDate);
            }
            if (!empty($toDate)) {
                $toDate = strtotime($toDate);
                $toDate = date('Y-m-d', $toDate);
            }

            $allSucessStates = [2];
            $allFailiureStates = [3,4];
            
            $leaveRequests = DB::table('leaveRequest')
                ->leftJoin('workflowInstance','workflowInstance.id','=','leaveRequest.workflowInstanceId')
                ->leftJoin('leaveCoveringPersonRequests','leaveCoveringPersonRequests.leaveRequestId','=','leaveRequest.id')
                ->leftJoin('employee','employee.id','=','leaveRequest.employeeId')
                ->leftJoin('employeeJob','employeeJob.id','=','employee.currentJobsId') 
                ->leftJoin('location','location.id','=','employeeJob.locationId')     
                ->leftJoin('department','department.id','=','employeeJob.departmentId')
                ->leftJoin('leaveType','leaveType.id','=','leaveRequest.leaveTypeId')   
                ->leftJoin('workflowState','workflowState.id','=','leaveRequest.currentState');   
                // ->selectRaw("CONCAT_WS(' ', firstName,  lastName) AS employeeName, employee.firstName, employee.lastName, employee.id as employeeIdNo")
                // ->selectRaw('leaveRequest.id,
                // leaveRequest.numberOfLeaveDates,
                // leaveRequest.fromDate,
                // leaveRequest.comments,
                // leaveRequest.toDate,
                // leaveRequest.fromTime,
                // leaveRequest.toTime,
                // leaveRequest.createdAt as date,
                // leaveRequest.reason,
                // leaveRequest.leaveTypeId,
                // leaveRequest.currentState, leaveCoveringPersonRequests.id as leaveCoveringRequestId, leaveCoveringPersonRequests.state as coveringPersonState')
                // ->selectRaw('employeeJob.locationId,
                // location.name as locationName,
                // department.name as departmentName,leaveType.name as leaveTypeName,
                // leaveType.leaveTypeColor as leaveTypeColor,
                // workflowInstance.priorState,workflowInstance.actionId,
                // workflowInstance.workflowId,
                // workflowInstance.id as workflowInstanceIdNo,
                // workflowState.label as StateLabel');


            if ($fromDate && $toDate) {
                $leaveRequests = $leaveRequests->where('leaveRequest.fromDate','>=',$fromDate)->where('leaveRequest.toDate','<=',$toDate);
            }

            if (is_array($employeeId)) { 
                $leaveRequests = $leaveRequests->whereIn('leaveRequest.employeeId', $employeeId);
            } else {
                $leaveRequests = $leaveRequests->where('leaveRequest.employeeId', $employeeId);
            }

            if ($accessType == 'admin' && $location) {
                if (is_array($location)) { 
                    $leaveRequests = $leaveRequests->whereIn('employeeJob.locationId', $location);
                } else {
                    $leaveRequests = $leaveRequests->where('employeeJob.locationId', $location);
                }
            }

            if (!empty($filter->leaveTypeId)) {
                $searchArr = $filter->leaveTypeId;
                $leaveRequests = $leaveRequests->whereIn('leaveType.id', $searchArr);
                
            } else {
                if ($leaveType && is_array($leaveType) && !empty($leaveType)) {
                    $leaveRequests = $leaveRequests->whereIn('leaveType.id', $leaveType);
                } elseif($leaveType && !is_array($leaveType)) {
                    $leaveRequests = $leaveRequests->where('leaveType.id', $leaveType);
                }
            }

            if ($department) {
                $leaveRequests = $leaveRequests->where('employeeJob.departmentId', $department);
            }

            if (!is_null($isWithInactiveEmployees) && $isWithInactiveEmployees === "false") {
                $leaveRequests = $leaveRequests->where('employee.isActive', true);
            }
            
            if ($status && sizeof($status->statusSet) > 0) {
                
                $filterStates = [];

                foreach ($status->statusSet as $key => $value) {
                    switch ($value) {
                        case 'Pending':
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, [1]) : [1]; 
                            break;
                        case 'Approved':
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, [2]) : [2]; 
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

                $leaveRequests = $leaveRequests->whereIn('leaveRequest.currentState', $filterStates);
            }

            $result = $leaveRequests->get(["leaveRequest.id","leaveRequest.numberOfLeaveDates","leaveRequest.fromDate","leaveRequest.comments","leaveRequest.toDate","leaveRequest.fromTime","leaveRequest.toTime","leaveRequest.createdAt as date","leaveRequest.reason","leaveRequest.leaveTypeId","leaveRequest.currentState","leaveCoveringPersonRequests.id as leaveCoveringRequestId","leaveCoveringPersonRequests.state as coveringPersonState","employee.firstName","employee.lastName","employee.id as employeeIdNo","employeeJob.locationId","location.name as locationName","department.name as departmentName","leaveType.name as leaveTypeName","leaveType.leaveTypeColor as leaveTypeColor","workflowInstance.currentStateId","workflowInstance.workflowId","workflowInstance.id as workflowInstanceIdNo","workflowState.label as StateLabel"])->toArray();

            //get covering Person not relate 
            $nonCoveringPersonLeaves = collect($result)->whereNull('leaveCoveringRequestId');

            //get covering Person relate leaves 
            if ($accessType != 'employee') {
                $coveringPersonLeaves = collect($result)->whereIn('coveringPersonState', ['APPROVED']);
            } else {
                $coveringPersonLeaves = collect($result)->whereIn('coveringPersonState', ['PENDING' ,'DECLINED', 'CANCELED']);
            }

            $leaveRequests = $nonCoveringPersonLeaves->merge($coveringPersonLeaves);
            $leaveRequestCount = $leaveRequests->count();


            if (empty($sort)) {
                $leaveRequests = $leaveRequests->sortByDesc('fromDate');
            } else {
                if (!empty($sort->name) && !empty($sort->order)) {

                    switch ($sort->name) {
                        case 'employeeName':
                            $leaveRequests = ($sort->order == 'DESC') ? $leaveRequests->sortByDesc("firstName") : $leaveRequests->sortBy("firstName") ;
                            break;
                        case 'fromDate':
                            $leaveRequests = ($sort->order == 'DESC') ? $leaveRequests->sortByDesc("fromDate") : $leaveRequests->sortBy("fromDate");
                            break;
                        case 'toDate':
                            $leaveRequests = ($sort->order == 'DESC') ? $leaveRequests->sortByDesc("toDate") : $leaveRequests->sortBy("toDate");
                            break;
                        case 'id':
                            $leaveRequests = ($sort->order == 'DESC') ? $leaveRequests->sortByDesc("id") : $leaveRequests->sortBy("id");
                            break;
                        default:
                            $leaveRequests = ($sort->order == 'DESC') ? $leaveRequests->sortByDesc("fromDate") : $leaveRequests->sortByDesc("fromDate");
                            break;
                    }
                } else {
                    $leaveRequests = $leaveRequests->sortByDesc("fromDate");
                }
            }


            if ($pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $leaveRequests = $leaveRequests->skip($skip)->take($pageCount);
            }
            
            $leaveRequestsArray = [];
                
            foreach ($leaveRequests as $key => $leave) {
                $leave = (array) $leave;
                if ($leave['leaveTypeId'] == 0) {
                    $leave['name'] = "Short Leave";
                    $leave['numberOfLeaveDates'] = "-";
                }
                $date = strtotime($leave['date']);
                $date = date('Y-m-d', $date);
                $leave['date'] = $date;

                
                array_push($leaveRequestsArray, $leave);
            }
            
            foreach ($leaveRequestsArray as $leaveRequestIndex => $leaveRequest) {
                $leaveRequest = (array) $leaveRequest;
                $workFlowId = $leaveRequest['workflowId'];
                $leaveRequestsArray[$leaveRequestIndex]['employeeName'] = $leaveRequest['firstName'].' '.$leaveRequest['lastName'];

                // if (!empty($leaveRequest['fromDate'])) {
                //     $leaveRequestsArray[$leaveRequestIndex]['fromDate'] = strtotime($leaveRequest['fromDate']);
                //     $leaveRequestsArray[$leaveRequestIndex]['fromDate'] = date('d-m-Y', $leaveRequestsArray[$leaveRequestIndex]['fromDate']);
                // }

                // if (!empty($leaveRequest['toDate'])) {
                //     $leaveRequestsArray[$leaveRequestIndex]['toDate'] = strtotime($leaveRequest['toDate']);
                //     $leaveRequestsArray[$leaveRequestIndex]['toDate'] = date('d-m-Y', $leaveRequestsArray[$leaveRequestIndex]['toDate']);
                // }
                if (!empty($workFlowId)) {

                    $workFlowData = DB::table('workflowDefine')
                        ->select('workflowDefine.id', 'workflowDefine.contextId')
                        ->where('workflowDefine.id', '=', $workFlowId)
                        ->where('workflowDefine.isDelete', '=', false)
                        ->first();
                    
                    $workFlowData = (array) $workFlowData;
    
                    $finaleStates = [2,3,4];
                    $priorState = 1;
    
                    if ($priorState != $leaveRequest['currentStateId']) {
                        $leaveRequestsArray[$leaveRequestIndex]['canCancel'] = false;
                    } else {
                        $leaveRequestsArray[$leaveRequestIndex]['canCancel'] = true;
                    }
    
                    if (!in_array($leaveRequest['currentStateId'], $finaleStates)) {
                        $leaveRequestsArray[$leaveRequestIndex]['canShowLeaveBalance'] = true;
                        $balanceData = $this->leaveTypeService->getEmployeeEntitlementCount($leaveRequest['fromDate'], $leaveRequest['employeeIdNo'], $leaveRequest['leaveTypeId']);
                        
                        if (!empty($balanceData['data'][0])) {
                            $dataSet = (array) $balanceData['data'][0];
                            $balanceDaysCount = $dataSet['total'] - ($dataSet['pending'] + $dataSet['used']);
                        } else {
                            $balanceDaysCount = 0;
                        }
                        
                        $leaveRequestsArray[$leaveRequestIndex]['leaveBalance'] = $balanceDaysCount; // Need to Calculate the leave balance and assign
                    } else {
                        $leaveRequestsArray[$leaveRequestIndex]['canShowLeaveBalance'] = false;
                        $leaveRequestsArray[$leaveRequestIndex]['leaveBalance'] = null; // Need to Calculate the leave balance and assign
                    }

                    $initialState =  1;
                    $leaveRequestsArray[$leaveRequestIndex]['canShowApprovalLevel'] = true;
                   
                    if ($leaveRequest['currentState'] == 4 && !empty($leaveRequest['workflowInstanceIdNo'])) {
                        $instanceApproveLevel =  DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', $leaveRequest['workflowInstanceIdNo'])->where('levelStatus', 'CANCELED')->where('levelSequence','!=',0)->first();

                        $leaveRequestsArray[$leaveRequestIndex]['canShowApprovalLevel'] = !is_null($instanceApproveLevel) ? true : false;
                    }

    
                } else {
                    $leaveRequestsArray[$leaveRequestIndex]['canShowLeaveBalance'] = false;
                    $leaveRequestsArray[$leaveRequestIndex]['canCancel'] = false;
                    // $leaveRequestsArray[$leaveRequestIndex]['StateLabel'] = 'Approved';   
                }


                //check leave request has covering person
                $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
                    ->leftJoin('employee','employee.id','=','leaveCoveringPersonRequests.coveringEmployeeId')
                    ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $leaveRequest['id'])
                    ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                    ->first();
                
                if (is_null($coveringPersonRequestData)) {
                    $leaveRequestsArray[$leaveRequestIndex]['hasPendingCoveringPersonRequests'] = false;
                    $leaveRequestsArray[$leaveRequestIndex]['manageCoveringPerson'] = false;
                    $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData'] = [];
                } else {
                    $leaveRequestsArray[$leaveRequestIndex]['hasPendingCoveringPersonRequests'] = ($coveringPersonRequestData->state === 'PENDING') ?  true : false;
                    $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData'] = $coveringPersonRequestData;
                    $leaveRequestsArray[$leaveRequestIndex]['manageCoveringPerson'] = true;

                    switch ($coveringPersonRequestData->state) {
                        case 'PENDING':
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->statusLabel = "Pending";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagFontColor = "#F8A325";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagColor = "#FFEFD8";
                            break;
                        case 'APPROVED':
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->statusLabel = "Approved";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagFontColor = "#389e0d";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagColor = "#F6FCED";
                            break;
                        case 'DECLINED':
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->statusLabel = "Declined";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagFontColor = "#CF1322";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagColor = "#FFF1F0";
                            break;
                        case 'CANCELED':
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->statusLabel = "Canceled";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagFontColor = "#1D39C4";
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagColor = "#F0F5FF";
                            break;
                        default:
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->statusLabel = null;
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagFontColor = null;
                            $leaveRequestsArray[$leaveRequestIndex]['coveringPersonRequestsData']->tagColor = null;
                            break;
                    }
                }

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
                $comments = json_decode($leaveRequest['comments']);
                $leaveRequestsArray[$leaveRequestIndex]['commentCount'] = sizeof($comments);
                $leaveRequestsArray[$leaveRequestIndex]['workflowInstanceId'] = (!is_null($leaveRequestsArray[$leaveRequestIndex]['workflowInstanceIdNo'])) ? $leaveRequestsArray[$leaveRequestIndex]['workflowInstanceIdNo'] : null;                
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

    public function getLeaveRequestRelatedComments($id)
    {
        try {
            
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            
            $relatedComments = json_decode($existingLeave->comments);

            $relatedComments = array_reverse($relatedComments);
            
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET_COMMENTS'), $relatedComments);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    public function getLeaveDateWiseDetails($id)
    {
        try {
            $leaveDatesArray = [];
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                DB::rollback();
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave;

            $leaveDateDetails = DB::table('leaveRequestDetail')
            ->where('leaveRequestDetail.leaveRequestId', $id);

            if ($existingLeave['currentState'] != 4 ) {
                $leaveDateDetails = $leaveDateDetails->whereIn('leaveRequestDetail.status', ['PENDING', 'APPROVED', 'REJECTED'])->orderBy('leaveDate', 'asc')->get();
            }  else {
                $leaveDateDetails = $leaveDateDetails->where('updatedAt', '>=',$existingLeave['updatedAt'])->orderBy('leaveDate', 'asc')->get();
            }

            if (is_null($leaveDateDetails)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }

            $rangeIndex = null;
            $rangeArr = [];
            $currentDayType = null;

            foreach ($leaveDateDetails as $key => $leaveDateDetail) {
                $leaveDateDetail = (array) $leaveDateDetail;
                $date = Carbon::parse($leaveDateDetail['leaveDate'])->format('d-m-Y');

                $leaveDatesArray[] =  [
                    "isCheckedFirstHalf" => ($leaveDateDetail['leavePeriodType'] == 'FULL_DAY' || $leaveDateDetail['leavePeriodType'] == 'FIRST_HALF_DAY') ? true : false,
                    "isCheckedSecondHalf" => ($leaveDateDetail['leavePeriodType'] == 'FULL_DAY' || $leaveDateDetail['leavePeriodType'] == 'SECOND_HALF_DAY') ? true : false,
                    "leavePeriodType" => $leaveDateDetail['leavePeriodType'],
                    "leavePeriodTypeLabel" => ($leaveDateDetail['leavePeriodType'] == 'FIRST_HALF_DAY' || $leaveDateDetail['leavePeriodType'] == 'SECOND_HALF_DAY') ? 'Half Day' : 'Full Day',
                    "date" => $date
                ];

                if ($rangeIndex == null) {
                    $rangeIndex = 1;
                } else {
                    if ($currentDayType != $leaveDateDetail['leavePeriodType']) {
                        $rangeIndex++;
                    }
                }
                $rangeKey = 'Range' . $rangeIndex;

                if ($currentDayType != $leaveDateDetail['leavePeriodType']) {
                    $rangeArr[$rangeKey]['fromDate'] = $leaveDateDetail['leaveDate'];
                    $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                    $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                    $currentDayType = $leaveDateDetail['leavePeriodType'];
                } else {
                    $nextDay = Carbon::parse($rangeArr[$rangeKey]['toDate'])->addDay()->format('Y-m-d');
                    // $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                    // $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                    // $currentDayType = $leaveDateDetail['leavePeriodType'];


                    if ($nextDay == $leaveDateDetail['leaveDate']) {
                        $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                        $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                        $currentDayType = $leaveDateDetail['leavePeriodType'];

                    } else {
                        if ($existingLeave['currentState'] != 4 ) {
                            $hasCancelDate = DB::table('leaveRequestDetail')
                                ->where('leaveRequestDetail.leaveRequestId', $id)
                                ->where('leaveRequestDetail.leaveDate',$nextDay)
                                ->where('leaveRequestDetail.leavePeriodType', $currentDayType)
                                ->where('leaveRequestDetail.status', 'CANCELED')->first();
                            if (!is_null($hasCancelDate)) {
                                $rangeIndex++;
                                $rangeKey = 'Range' . $rangeIndex;
                                $rangeArr[$rangeKey]['fromDate'] = $leaveDateDetail['leaveDate'];
                                $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                                $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                                $currentDayType = $leaveDateDetail['leavePeriodType'];
                            } else {
                                $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                                $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                                $currentDayType = $leaveDateDetail['leavePeriodType'];
                            }

                        } else {
                            $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                            $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                            $currentDayType = $leaveDateDetail['leavePeriodType'];
                        }
                    }

                    // if ($nextDay == $leaveDateDetail['leaveDate']) {
                        // $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                        // $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                        // $currentDayType = $leaveDateDetail['leavePeriodType'];
                    // } else {
                        // $rangeIndex++;
                        // $rangeKey = 'Range' . $rangeIndex;
                        // $rangeArr[$rangeKey]['fromDate'] = $leaveDateDetail['leaveDate'];
                        // $rangeArr[$rangeKey]['toDate'] = $leaveDateDetail['leaveDate'];
                        // $rangeArr[$rangeKey]['leavePeriodType'] = $leaveDateDetail['leavePeriodType'];
                        // $currentDayType = $leaveDateDetail['leavePeriodType'];
                    // }

                }

            }

            $dataSet = [
                'leaveDatesArray' => $leaveDatesArray,
                'leaveDateRangeArray' => array_values($rangeArr)
            ];

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET_COMMENTS'), $dataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function add comment.
     *
     * @param $id leave id
     * @param $leave array containing comment data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"     
     */
    public function addLeaveRequestComment($id, $commentData)
    {
        try {
            
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave; 

            $oldComments = json_decode($existingLeave['comments']);

            $commentData['id'] = rand(10,10000);
            $commentData['commentedUser'] = $this->session->user->firstName.' '.$this->session->user->lastName;
            array_push($oldComments, $commentData);
            $existingLeave['comments'] = json_encode($oldComments);

            $result = $this->store->updateById($this->leaveRequestModel, $id, $existingLeave);

            if (!$result) {
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_ADD_COMMENT'), $existingLeave);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), null);
        }
    }

    private function leaveEntitlementAllocation($employeeId, $leaveType, $date, $entitlePortion, $leaveRequestEntitlements)
    {
        try {
            $priorityList = collect($this->store->getFacade()::table($this->leaveEntitlementModel->getName())
                ->select(DB::raw('*, (entilementCount - (usedCount + pendingCount)) AS balance'))
                ->where('employeeId', $employeeId)
                ->where('leaveTypeId', $leaveType->id)
                ->where('validTo', '>=', $date)
                ->where('validFrom', '<=', $date)
                ->where('isDelete',false)
                ->having('balance', '>', 0)
                ->orderByRaw("CASE type WHEN 'CARRY_FORWARD' THEN 1 ELSE 2 END")
                ->orderBy('validTo', 'asc')
                ->orderByRaw('ABS(TIMESTAMPDIFF(DAY, validTo, validFrom)) asc')
                ->orderBy('createdAt', 'asc')
                ->get())->toArray();

            $index = 0;
            $allocatedEntitlements = [];
            $allowExceedingBalance = $leaveType->allowExceedingBalance ?? false;

            while ($entitlePortion > 0) {
                $entitlement = $priorityList[$index] ?? null;

                if (empty($entitlement)) {
                    if ($allowExceedingBalance) {
                        $allocatedEntitlement = array(
                            'date' => $date,
                            'leaveEntitlementId' => null,
                            'entitlePortion' => $entitlePortion
                        );

                        $allocatedEntitlements[] = $allocatedEntitlement;
                        return $allocatedEntitlements;
                    } else {
                        return null;
                    }
                }

                $allocatedCount = array_sum(
                    array_column(
                        array_filter($leaveRequestEntitlements, function ($leaveRequestEntitlement) use ($entitlement) {
                            return $leaveRequestEntitlement['leaveEntitlementId'] == $entitlement->id;
                        }
                    ), 'entitlePortion')
                );

                $availableCount = $entitlement->balance - $allocatedCount;

                if ($availableCount > $entitlePortion) {
                    $allocatedEntitlement = array(
                        'date' => $date,
                        'leaveEntitlementId' => $entitlement->id,
                        'entitlePortion' => $entitlePortion
                    );
                    $entitlePortion = 0;
                } else if ($availableCount > 0) {
                    $allocatedEntitlement = array(
                        'date' => $date,
                        'leaveEntitlementId' => $entitlement->id,
                        'entitlePortion' => $availableCount
                    );
                    $entitlePortion = $entitlePortion - $availableCount;
                }

                if (!empty($allocatedEntitlement)) {
                    $allocatedEntitlements[] = $allocatedEntitlement;
                }

                $index++;
            }

            return $allocatedEntitlements;
        } catch (Exception $e) {
            Log::error('leaveEntitlementAllocation > ' . $e->getMessage());
            return [];
        }
    }

    private function isCalendarWorkingDay($leaveType, $employeeCalendar, $date)
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

        $workingDayIds = collect($leaveType->workingDayIds)->toArray();

        return 
            $dayTypeId == 1 ||
            !empty(array_filter($workingDayIds, function ($day) use ($dayTypeId) {
                return $day->dayTypeId == $dayTypeId;
            }));
    }

    /**
     * TODO:: need to enhance this function
     */
    public function getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $dateObject)
    {
        $isCalendarWorkingDay = $this->isCalendarWorkingDay($leaveType, $employeeCalendar, $dateObject);
        // get calendar id
        $calendarId = $employeeCalendar[0]->calendarId;
        $shift = $this->getEmployeeWorkShiftByCalendarId($employeeId, $dateObject, $calendarId);

        return !empty($shift) && ($isCalendarWorkingDay || !empty($shift->isEmployeeWorkPattern) || !empty($shift->isAdhocWorkshift))
            ? $shift
            : null;
    }

    public function cancelLeaveRequest($id) {
        try {
            
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave; 
            if (!empty($existingLeave['workflowInstanceId'])) {
                $actionIds = [];
                // state id 4 belongs to cancelled
                $instanceUpdated['currentStateId'] = 4;
                $no = $this->store->updateById($this->workflowInstanceModel, $existingLeave['workflowInstanceId'], $instanceUpdated);
            }

            $res = $this->reverseLeaveEntitlementAllocations($id, 'CANCELED');

            if ($res['error']) {
                DB::rollback();
                return $this->error($res['statusCode'], $res['message'], $$id);
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_CANCEL'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_CANCEL'), null);
        }
    }


    public function cancelAdminAssignLeaveRequest($id) {
        try {
            DB::beginTransaction();
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave; 
            $employeeId = $existingLeave['employeeId'];

            $leaveDateDetails = DB::table('leaveRequestDetail')->where('leaveRequestDetail.leaveRequestId', $id)->get();

            $leaveDates = [];
            foreach ($leaveDateDetails as $leaveDateKey => $leaveDateDataObj) {
                $leaveDateDataObj = (array) $leaveDateDataObj;
                $leaveDates[] = $leaveDateDataObj['leaveDate'];
            }

            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);
            
            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }
            
            //change the leave request current state as cancelled
            $leaveRequestUpdated['currentState'] = 4;
            $updateCurrentState = $this->store->updateById($this->leaveRequestModel, $existingLeave['id'], $leaveRequestUpdated);

            $res = $this->reverseLeaveEntitlementAllocations($id, 'CANCELED', true);

            if ($res['error']) {
                DB::rollback();
                return $this->error($res['statusCode'], $res['message'], $id);
            }

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

    public function reverseLeaveEntitlementAllocations($id, $status, $isReversUsedCount = false, $isOnlyAllocationReverse = false) {

        try {
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave; 

            $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
                ->where('leaveRequestId', $existingLeave['id'])
                ->whereIn('status', ['PENDING', 'APPROVED'])
                ->get()->toArray();

            foreach ($leaveRequestDetails as $key => $value) {
                $value = (array) $value;
                if (!$isOnlyAllocationReverse) {
                    //change leave request detail
                    $detail['status'] = $status;
                    $detail['updatedAt'] = Carbon::now()->toDateTimeString();
                    $updateDetailStatus = $this->store->updateById($this->leaveRequestDetailModel, $value['id'], $detail);
    
                    if (!$updateDetailStatus) {
                        return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $id);
                    }
                }

                //change entilement portion from leave entitlement
                $leaveRequestEntitlements = $this->store->getFacade()::table('leaveRequestEntitlement')
                    ->where('leaveRequestDetailId', $value['id'])
                    ->get()->toArray();
                foreach ($leaveRequestEntitlements as $key => $leaveRequestEntitlement) {
                    $leaveRequestEntitlement = (array) $leaveRequestEntitlement;
                    $entitlementPortion = $leaveRequestEntitlement['entitlePortion'];
                    $entitlementId = $leaveRequestEntitlement['leaveEntitlementId'];
                    
                    if (!is_null($entitlementId)) {
                        $existingEntitlement = $this->store->getById($this->leaveEntitlementModel, $entitlementId);
                        if (is_null($existingLeave)) {
                            return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
                        }
                        $existingEntitlement = (array) $existingEntitlement;
    
                        if ($isReversUsedCount) {
                            $existingEntitlement['usedCount'] -= $entitlementPortion;
                        } else {
                            $existingEntitlement['pendingCount'] -= $entitlementPortion;
                        }
    
                        $updateEntitlePendingCount = $this->store->updateById($this->leaveEntitlementModel, $entitlementId, $existingEntitlement);
                    }

                    if (!$isOnlyAllocationReverse) {
                        $updateLeaveEntitlementPortion = $this->store->getFacade()::table('leaveRequestEntitlement')->where('id', $leaveRequestEntitlement['id'])->update(['entitlePortion' => 0]);
                    }
                } 
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_REVERSE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_REVERSE'), null);
        }

    }


    public function setLeaveEntitlementAllocations($id) {

        try {
            
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave; 

            $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
                ->where('leaveRequestId', $existingLeave['id'])
                ->where('status','APPROVED')
                ->get()->toArray();
            foreach ($leaveRequestDetails as $key => $value) {
                //change leave request detail
                $value = (array) $value;

                //set entilement portion to use count 
                $leaveRequestEntitlements = $this->store->getFacade()::table('leaveRequestEntitlement')
                    ->where('leaveRequestDetailId', $value['id'])
                    ->get()->toArray();
                foreach ($leaveRequestEntitlements as $key => $leaveRequestEntitlement) {
                    $leaveRequestEntitlement = (array) $leaveRequestEntitlement;
                    $entitlementPortion = $leaveRequestEntitlement['entitlePortion'];
                    $entitlementId = $leaveRequestEntitlement['leaveEntitlementId'];

                    if (!empty($entitlementId)) {
                        $existingEntitlement = $this->store->getById($this->leaveEntitlementModel, $entitlementId);
                        if (is_null($existingLeave)) {
                            return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
                        }
                        $existingEntitlement = (array) $existingEntitlement;
    
                        if ($value['status'] == 'APPROVED') {
                            //set use count
                            $usedCount = (!empty($existingEntitlement['usedCount'])) ? $existingEntitlement['usedCount'] : 0;
                            $usedCount += $entitlementPortion;
                        }
    
                        //reduce pending count
                        $pendingCount = $existingEntitlement['pendingCount'];
                        $pendingCount -= $entitlementPortion;
    
                        $existingEntitlement['usedCount'] = $usedCount;
                        $existingEntitlement['pendingCount'] = $pendingCount;
                        $updateEntitlePendingCount = $this->store->updateById($this->leaveEntitlementModel, $entitlementId, $existingEntitlement);
                    }

                } 
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_SET_ENTITLE_ALLOCATION'), []);
            
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_SET_ENTITLE_ALLOCATION'), null);
        }
    }

    public function getLeaveEntitlementUsage($request)
    {
        try {
            $tableOptions = new stdClass();
            $tableOptions->pageNo = $request->query('pageNo', null);
            $tableOptions->pageCount = $request->query('pageCount', null);
            $tableOptions->sort = json_decode($request->query('sort', null));
            $tableOptions->filter = $request->query('filter', null);

            $dataType = $request->query('dataType', null); // table / report
            $reportType = $request->query('reportType', null); // employee / leave type
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            $leaveEntitlementUsage = [];
            if ($reportType === 'leaveType') {
                $searchOptions = new stdClass();
                $searchOptions->jobTitle = $request->query('jobTitle', null);
                $searchOptions->location = $request->query('location', null);
                $searchOptions->leaveType = $request->query('leaveType', null);
                $searchOptions->department = $request->query('department', null);
                $searchOptions->leavePeriod = $request->query('leavePeriod', null);
                $searchOptions->activeState = $request->query('activeState', null);
                $searchOptions->entityId = $request->query('entityId', 1);

                $leaveEntitlementUsage = $this->getLeaveEntitlementUsageLeaveType($dataType, $tableOptions, $searchOptions);
            } else if ($reportType === 'employee') {
                $employeeId = $request->query('employee', null);

                if (empty($employeeId)) {
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null); //TODO no emp message                
                }
                // for filter permitted employees
                if (!in_array($employeeId, $permittedEmployeeIds)) {
                    return $this->error(403, Lang::get('leaveRequestMessages.basic.ERR_NOT_PERMITTED'), null);
                }
                $leavePeriod = $request->query('leavePeriod', null);
                $leaveEntitlementUsage = $this->getLeaveEntitlementUsageEmployee($dataType, $tableOptions, $employeeId, $leavePeriod);
            } else if ($reportType === 'leaveEntitlement') {
                $searchOptions = new stdClass();
                $leaveType = $request->query('leaveType', null);
                $searchOptions->leaveTypes = (!empty($leaveType)) ? json_decode($leaveType) : [];
                $searchOptions->leavePeriod = $request->query('leavePeriod', null);
                $searchOptions->activeState = $request->query('activeState', null);
                $searchOptions->entityId = $request->query('entityId', 1);

                $leaveEntitlementUsage = $this->getLeaveEntitlementUsageForLeaveEntitlement($dataType, $tableOptions, $searchOptions);
            } else if ($reportType === 'employeeLeaveRequestReport') {
                $employeeId = $request->query('employee', null);
                $requestType = $request->query('requestType', null);
                $requestStatus = $request->query('requestStatus', null);
                
                if ($requestType == 'leave') {
                    $leaveEntitlementUsage = $this->getLeaveRequestReportByEmployee($employeeId, $requestStatus, $requestType, $tableOptions, $dataType);
                } else if ($requestType == 'short-leave') {
                    $leaveEntitlementUsage = $this->getShortLeaveRequestReportByEmployee($employeeId, $requestStatus, $requestType, $tableOptions, $dataType);
                }
            } else if ($reportType === 'leaveSummaryReport') {
                $employees = $request->query('employee', null);
                $employees = (!is_null($employees)) ? json_decode($employees) : [];
                $leaveStatuses = $request->query('leaveStatus', null);
                $leaveStatuses = (!is_null($leaveStatuses)) ? json_decode($leaveStatuses): [];
                $activeState = $request->query('activeState', null);
                $entityId = $request->query('entityId', 1);
                $fromDate = $request->query('fromDate', null);
                $toDate = $request->query('toDate', null);
                $columnHeadersData = $request->query('columnHeaders', null);
                $columnHeaders = json_decode($columnHeadersData);
                
                $leaveEntitlementUsage = $this->getLeaveSummaryReportData($employees, $fromDate, $toDate, $tableOptions->pageNo, $tableOptions->pageCount, $tableOptions->sort,null, $leaveStatuses, $activeState, $entityId, $dataType, $columnHeaders );
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveEntitlementUsage);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    private function getLeaveSummaryReportData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $isWithInactiveEmployees, $entityId, $dataType, $columnHeaders)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $empIdArrString= "";
            $locIdArrString= "";
            $filterFieldArr = [];
            $statusIds = null;
            
            if (!empty($fromDate)) {
                $fromDate = strtotime($fromDate);
                $fromDate = date('Y-m-d', $fromDate);
            }
            if (!empty($toDate)) {
                $toDate = strtotime($toDate);
                $toDate = date('Y-m-d', $toDate);
            }

            $allSucessStates = [2];
            $allFailiureStates = [3,4];
            
            $leaveRequests = DB::table('leaveRequest')
                ->leftJoin('workflowInstance','workflowInstance.id','=','leaveRequest.workflowInstanceId')
                ->leftJoin('leaveCoveringPersonRequests','leaveCoveringPersonRequests.leaveRequestId','=','leaveRequest.id')
                ->leftJoin('employee','employee.id','=','leaveRequest.employeeId')
                ->leftJoin('employeeJob','employeeJob.id','=','employee.currentJobsId') 
                ->leftJoin('location','location.id','=','employeeJob.locationId')     
                ->leftJoin('department','department.id','=','employeeJob.departmentId')
                ->leftJoin('leaveType','leaveType.id','=','leaveRequest.leaveTypeId')   
                ->leftJoin('workflowState','workflowState.id','=','leaveRequest.currentState');   

            if ($fromDate && $toDate) {
                $leaveRequests = $leaveRequests->where('leaveRequest.fromDate','>=',$fromDate)->where('leaveRequest.toDate','<=',$toDate);
            }

            // for filter only permitted employees results
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (is_array($employeeId) && !empty($employeeId)) { 
                $permittedEmployeeIds = $employeeId;
            } 

            $leaveRequests = $leaveRequests->whereIn('leaveRequest.employeeId', $permittedEmployeeIds);

            if ($entityId) {
                $entityIds = $this->getParentEntityRelatedChildNodes((int)$entityId);
                array_push($entityIds, (int)$entityId);

                $leaveRequests = $leaveRequests->whereIn('employeeJob.orgStructureEntityId', $entityIds);
            }

            if (!is_null($isWithInactiveEmployees) && $isWithInactiveEmployees === "false") {
                $leaveRequests = $leaveRequests->where('employee.isActive', true);
            }
            
            if (!empty($status)) {
                $leaveRequests = $leaveRequests->whereIn('leaveRequest.currentState', $status);
            }

            $result = $leaveRequests->get(["leaveRequest.id","leaveRequest.numberOfLeaveDates","leaveRequest.fromDate","leaveRequest.comments","leaveRequest.toDate","leaveRequest.fromTime","leaveRequest.toTime","leaveRequest.createdAt as date","leaveRequest.reason","leaveRequest.leaveTypeId","leaveRequest.currentState","leaveCoveringPersonRequests.id as leaveCoveringRequestId","leaveCoveringPersonRequests.state as coveringPersonState","employee.firstName","employee.lastName","employee.id as employeeIdNo","employee.employeeNumber","employeeJob.locationId","employeeJob.reportsToEmployeeId","employeeJob.orgStructureEntityId","location.name as locationName","department.name as departmentName","leaveType.name as leaveTypeName","leaveType.leaveTypeColor as leaveTypeColor","workflowInstance.currentStateId","workflowInstance.workflowId","workflowInstance.id as workflowInstanceIdNo","workflowState.label as StateLabel"])->toArray();

            //get covering Person not relate 
            $nonCoveringPersonLeaves = collect($result)->whereNull('leaveCoveringRequestId');

            //get covering Person relate leaves 
            $coveringPersonLeaves = collect($result)->whereIn('coveringPersonState', ['APPROVED']);

            $leaveRequests = $nonCoveringPersonLeaves->merge($coveringPersonLeaves);
            $leaveRequestCount = $leaveRequests->count();


            $leaveRequests = $leaveRequests->sortByDesc("employeeNumber");
           
            if ($dataType === 'table' && $pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $leaveRequests = $leaveRequests->skip($skip)->take($pageCount);
            }
            
            $leaveRequestsArray = [];
                
            foreach ($leaveRequests as $key => $leave) {
                $leave = (array) $leave;
                if ($leave['leaveTypeId'] == 0) {
                    $leave['name'] = "Short Leave";
                    $leave['numberOfLeaveDates'] = "-";
                }
                $date = strtotime($leave['date']);
                $date = date('Y-m-d', $date);
                $leave['date'] = $date;

                
                array_push($leaveRequestsArray, $leave);
            }
            
            foreach ($leaveRequestsArray as $leaveRequestIndex => $leaveRequest) {
                $leaveRequest = (array) $leaveRequest;
                $workFlowId = $leaveRequest['workflowId'];
                $leaveRequestsArray[$leaveRequestIndex]['employeeName'] = $leaveRequest['firstName'].' '.$leaveRequest['lastName'];


                $reportingPerson = null;
                if (!empty($leaveRequest['reportsToEmployeeId'])) {
                    //check leave request has covering person
                    $reportingPerson = DB::table('employee')
                        ->select('employee.id', 'employee.firstName','employee.lastName')
                        ->where('id', '=', $leaveRequest['reportsToEmployeeId'])
                        ->where('employee.isDelete', '=', false)
                        ->where('employee.isActive', '=', true)
                        ->first();
                        
                }
                    
                $leaveRequestsArray[$leaveRequestIndex]['reportsTo'] = (!is_null($reportingPerson)) ? $reportingPerson->firstName.' '.$reportingPerson->lastName : '-';
                $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');


                foreach ($orgHierarchyConfig as $orgKey => $hirarchy) {
                    $leaveRequestsArray[$leaveRequestIndex][$orgKey] = null;
                }

                if (!empty($leaveRequest['orgStructureEntityId'])) {
                    $employeeEntityData = $this->getEntityById($leaveRequest['orgStructureEntityId']);

                    foreach ($employeeEntityData as $entityKey => $entityData) {
                        $leaveRequestsArray[$leaveRequestIndex][$entityData->entityLevel] = $entityData->name;
                    }
                }

                //check leave request has covering person
                $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
                    ->leftJoin('employee','employee.id','=','leaveCoveringPersonRequests.coveringEmployeeId')
                    ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $leaveRequest['id'])
                    ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                    ->first();


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
                $comments = json_decode($leaveRequest['comments']);
                $leaveRequestsArray[$leaveRequestIndex]['commentCount'] = sizeof($comments);
                $leaveRequestsArray[$leaveRequestIndex]['workflowInstanceId'] = (!is_null($leaveRequestsArray[$leaveRequestIndex]['workflowInstanceIdNo'])) ? $leaveRequestsArray[$leaveRequestIndex]['workflowInstanceIdNo'] : null;                
            }

            if ($dataType === 'table') {
                return $leaveRequestsArray;
            } else {
                $headerArray = [];
                $columnMappingDataIndexs = [];
                $cellOneLetter = 'A';
                $currenttLetter = '';
                foreach ($columnHeaders as $key => $columnData) {
                    $columnData = (array) $columnData;
                    if ($columnData['isShowColumn']) {
                        if ($currenttLetter == '') {
                            $currenttLetter = $cellOneLetter;
                        } else {
                            $currenttLetter++;
                        }
                        array_push($headerArray, $columnData['name']);
                        $columnMappingDataIndexs[$columnData['name']] = $columnData['mappedDataIndex'];
                    }
                }   
                $cellRange =  $cellOneLetter.'1:'.$currenttLetter.'1';    
                $report ="leaveSummaryReport";
                $fileData = $this->downloadLeaveSummaryReport($headerArray, $leaveRequestsArray, $cellRange ,$report, $columnMappingDataIndexs);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }
            
            
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function downloadLeaveSummaryReport($headerArray, $dataSetArray, $headerColumnCellRange ,$report, $columnMappingDataIndexs)
    {
        try {
            $excelData = Excel::download(new LeaveSummaryReportExcelExport($headerArray, $dataSetArray, $headerColumnCellRange,$report, $columnMappingDataIndexs), 'dailyAttendanceReport.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return base64_encode($fileData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getEntityById($id)
    {
        try {
            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $entities = $this->store->getFacade()::table($this->orgEntityModel->getName())
                ->where('isDelete', false)
                ->get(['id', 'name', 'parentEntityId', 'entityLevel', 'headOfEntityId'])
                ->toArray();

            $nextId = $id;
            $response = [];

            do {
                $index = array_search($nextId, array_column($entities, 'id'));
                $entity = $entities[$index] ?? null;

                if (is_null($entity)) break;

                $response[$orgHierarchyConfig[$entity->entityLevel]] = $entity;
                $nextId = $entity->parentEntityId;
            } while ($nextId != null);

            return array_reverse($response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_GETALL'), null);
        }
    }


    private function getLeaveRequestReportByEmployee($employeeId, $requestStatus, $requestType, $tableOptions, $dataType)
    {
        try {
        
            $leaveRequests = DB::table('leaveRequest')
            ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'leaveRequest.workflowInstanceId')
            ->leftJoin('employee', 'employee.id', '=', 'leaveRequest.employeeId')            
            ->leftJoin('leaveType', 'leaveType.id', '=', 'leaveRequest.leaveTypeId')
            ->leftJoin('workflowState', 'workflowState.id', '=', 'leaveRequest.currentState')
            ->selectRaw("CONCAT_WS(' ', firstName,  lastName) AS employeeName, employee.employeeNumber")
            ->selectRaw('leaveRequest.id,
                leaveRequest.numberOfLeaveDates,
                leaveRequest.fromDate,
                leaveRequest.comments,
                leaveRequest.toDate,
                leaveRequest.fromTime,
                leaveRequest.toTime,
                leaveRequest.createdAt as requestedDate,
                leaveRequest.reason,
                leaveRequest.leaveTypeId,
                leaveRequest.currentState')
            ->selectRaw('leaveType.name as leaveTypeName,
                leaveType.leaveTypeColor as leaveTypeColor,
                workflowInstance.currentStateId,
                workflowInstance.workflowId,
                workflowInstance.id as workflowInstanceIdNo,
                workflowState.label as StateLabel');

            $leaveRequests = $leaveRequests->where('leaveRequest.employeeId', $employeeId)->whereNotNull('leaveRequest.workflowInstanceId');
            
            if (!empty($requestStatus)) {
                $leaveRequests = $leaveRequests->where('leaveRequest.currentState', $requestStatus);
            }

            $leaveRequestCount = $leaveRequests->count();
            if ($dataType === 'table' && $tableOptions->pageNo && $tableOptions->pageCount) {
                $skip = ($tableOptions->pageNo - 1) * $tableOptions->pageCount;
                $leaveRequests = $leaveRequests->skip($skip)->take($tableOptions->pageCount);


                if(isset($tableOptions->sort) && $tableOptions->sort !== null) {
                    if ($tableOptions->sort->name === 'fromDate') {
                        $leaveRequests = $leaveRequests->orderBy("leaveRequest.fromDate", $tableOptions->sort->order);
                    } else if ($tableOptions->sort->name === 'toDate') {
                        $leaveRequests = $leaveRequests->orderBy("leaveRequest.toDate", $tableOptions->sort->order);

                    } else {
                        $leaveRequests = $leaveRequests->orderBy("leaveRequest.id", 'DESC');
                    }
                } else {
                    $leaveRequests = $leaveRequests->orderBy("leaveRequest.id", 'DESC');
                }
            }

            $leaveRequests = $leaveRequests->get();

            foreach ($leaveRequests as $leaveKey => $leave) {
                
                //get related leavel Detail
                $levelDetails = DB::table('workflowInstanceApprovalLevel')
                ->selectRaw('workflowInstanceApprovalLevel.id as workflowInstanceApprovalLevelId,
                workflowInstanceApprovalLevel.levelSequence,
                workflowInstanceApprovalLevel.levelStatus,
                workflowInstanceApprovalLevelDetail.performAction,
                workflowAction.actionName,
                user.firstName,
                user.lastName,
                workflowInstanceApprovalLevelDetail.performBy')
                ->leftJoin('workflowInstanceApprovalLevelDetail', 'workflowInstanceApprovalLevelDetail.workflowInstanceApproverLevelId', '=', 'workflowInstanceApprovalLevel.id')
                ->leftJoin('workflowAction', 'workflowAction.id', '=', 'workflowInstanceApprovalLevelDetail.performAction')
                ->leftJoin('user', 'user.id', '=', 'workflowInstanceApprovalLevelDetail.performBy')
                ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)
                ->where('workflowInstanceApprovalLevel.workflowInstanceId', $leave->workflowInstanceIdNo);

                if ($requestStatus == 3 || $requestStatus == 4) {
                    $statusLabel = ($requestStatus == 3) ? 'REJECTED' : 'CANCELED';
                    $levelDetails = $levelDetails->where('workflowInstanceApprovalLevel.levelStatus', $statusLabel)->where('workflowInstanceApprovalLevelDetail.performAction', $requestStatus);
                }

                $levelDetails =  $levelDetails->get();
                $approvalLevelString = '';
                foreach ($levelDetails as $levelDetailKey => $levelDetail) {
                    $levelDetail = (array)$levelDetail;
                    $tempString = '';

                    if (!empty($levelDetail)) {
                        $levelKey = 'Level '.$levelDetail['levelSequence'];
                        $levelString = (!empty($levelDetail['performBy'])) ? $levelKey.' : '.$levelDetail['firstName'].' '.$levelDetail['lastName'] : $levelKey.' : -';
                        if ($tempString == '') {
                            $tempString = $levelString;
                        } else {
                            $tempString = $tempString.' | '.$levelString;
                        }

                    }
                    $approvalLevelString = ($approvalLevelString=='') ?  $tempString : $approvalLevelString.' | '.$tempString;

                    
                }

                if ($requestStatus == 4 && $approvalLevelString == '') {
                    $approvalLevelString = 'Level 0 : '. $leave->employeeName;
                }


                $leaveRequests[$leaveKey]->levelApproveDetails = $approvalLevelString;

            }

            
            if ($dataType === 'table') {
                return $leaveRequests;
            } else {
                $headerArray = ['Employee Number','Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Leave Count', 'Reason', 'Request Status', 'Approver Name'];
                $report ="employeeLeaveRequestReport";
                $fileData = $this->downloadLeaveRequestReport($headerArray, $leaveRequests, 'A1:I1',$report);
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    private function getShortLeaveRequestReportByEmployee($employeeId, $requestStatus, $requestType, $tableOptions, $dataType)
    {
        try {
        
            $shortLeaveRequests = DB::table('shortLeaveRequest')
            ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'shortLeaveRequest.workflowInstanceId')
            ->leftJoin('employee', 'employee.id', '=', 'shortLeaveRequest.employeeId')            
            ->leftJoin('workflowState', 'workflowState.id', '=', 'shortLeaveRequest.currentState')
            ->selectRaw("CONCAT_WS(' ', firstName,  lastName) AS employeeName, employee.employeeNumber")
            ->selectRaw('shortLeaveRequest.id,
                shortLeaveRequest.date,
                shortLeaveRequest.fromTime,
                shortLeaveRequest.toTime,
                shortLeaveRequest.shortLeaveType,
                shortLeaveRequest.numberOfMinutes,
                shortLeaveRequest.createdAt as requestedDate,
                shortLeaveRequest.reason,
                shortLeaveRequest.currentState')
            ->selectRaw('
                workflowInstance.currentStateId,
                workflowInstance.workflowId,
                workflowInstance.id as workflowInstanceIdNo,
                workflowState.label as StateLabel');

            $shortLeaveRequests = $shortLeaveRequests->where('shortLeaveRequest.employeeId', $employeeId)->whereNotNull('shortLeaveRequest.workflowInstanceId');
            
            if (!empty($requestStatus)) {
                $shortLeaveRequests = $shortLeaveRequests->where('shortLeaveRequest.currentState', $requestStatus);
            }

           
            $shortLeaveRequestCount = $shortLeaveRequests->count();

            if ($dataType === 'table' && $tableOptions->pageNo && $tableOptions->pageCount) {
                $skip = ($tableOptions->pageNo - 1) * $tableOptions->pageCount;
                $shortLeaveRequests = $shortLeaveRequests->skip($skip)->take($tableOptions->pageCount);


                if(isset($tableOptions->sort) && $tableOptions->sort !== null) {
                    if ($tableOptions->sort->name === 'fromDate') {
                        $shortLeaveRequests = $shortLeaveRequests->orderBy("shortLeaveRequest.date", $tableOptions->sort->order);
                    }  else {
                        $shortLeaveRequests = $shortLeaveRequests->orderBy("shortLeaveRequest.id", 'DESC');
                    }
                } else {
                    $shortLeaveRequests = $shortLeaveRequests->orderBy("shortLeaveRequest.id", 'DESC');
                }

            }

            $shortLeaveRequests = $shortLeaveRequests->get();

            foreach ($shortLeaveRequests as $leaveKey => $leave) {
                
                //get related leavel Detail
                $levelDetails = DB::table('workflowInstanceApprovalLevel')
                ->selectRaw('workflowInstanceApprovalLevel.id as workflowInstanceApprovalLevelId,
                workflowInstanceApprovalLevel.levelSequence,
                workflowInstanceApprovalLevel.levelStatus,
                workflowInstanceApprovalLevelDetail.performAction,
                workflowAction.actionName,
                user.firstName,
                user.lastName,
                workflowInstanceApprovalLevelDetail.performBy')
                ->leftJoin('workflowInstanceApprovalLevelDetail', 'workflowInstanceApprovalLevelDetail.workflowInstanceApproverLevelId', '=', 'workflowInstanceApprovalLevel.id')
                ->leftJoin('workflowAction', 'workflowAction.id', '=', 'workflowInstanceApprovalLevelDetail.performAction')
                ->leftJoin('user', 'user.id', '=', 'workflowInstanceApprovalLevelDetail.performBy')
                ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)
                ->where('workflowInstanceApprovalLevel.workflowInstanceId', $leave->workflowInstanceIdNo);

                if ($requestStatus == 3 || $requestStatus == 4) {
                    $statusLabel = ($requestStatus == 3) ? 'REJECTED' : 'CANCELED';
                    $levelDetails = $levelDetails->where('workflowInstanceApprovalLevel.levelStatus', $statusLabel)->where('workflowInstanceApprovalLevelDetail.performAction', $requestStatus);
                }

                $levelDetails =  $levelDetails->get();
                $approvalLevelString = '';
                foreach ($levelDetails as $levelDetailKey => $levelDetail) {
                    $levelDetail = (array)$levelDetail;
                    $tempString = '';

                    if (!empty($levelDetail)) {
                        $levelKey = 'Level '.$levelDetail['levelSequence'];
                        $levelString = (!empty($levelDetail['performBy'])) ? $levelKey.' : '.$levelDetail['firstName'].' '.$levelDetail['lastName'] : $levelKey.' : -';
                        if ($tempString == '') {
                            $tempString = $levelString;
                        } else {
                            $tempString = $tempString.' | '.$levelString;
                        }

                    }
                    $approvalLevelString = ($approvalLevelString=='') ?  $tempString : $approvalLevelString.' | '.$tempString;

                    
                }

                if ($requestStatus == 4 && $approvalLevelString == '') {
                    $approvalLevelString = 'Level 0 : '. $leave->employeeName;
                }


                $shortLeaveRequests[$leaveKey]->levelApproveDetails = $approvalLevelString;
                $shortLeaveRequests[$leaveKey]->hours = gmdate("H:i", $leave->numberOfMinutes * 60);
                $shortLeaveRequests[$leaveKey]->fromDate = $leave->date;

            }

            if ($dataType === 'table') {
                return $shortLeaveRequests;
            } else {
                $headerArray = ['Employee Number','Employee Name', 'Date', 'Hours', 'Reason', 'Request Status', 'Approver Name'];
                $report ="employeeShortLeaveRequestReport";
                $fileData = $this->downloadLeaveRequestReport($headerArray, $shortLeaveRequests, 'A1:G1',$report);
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function getLeaveEntitlementUsageEmployee($dataType, $tableOptions, $employeeId, $leavePeriod)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';

            if ($employeeId) {
                $whereQuery = "WHERE leaveEntitlement.employeeId = " . $employeeId;
            }

            if ($leavePeriod) {
                $dateObj = Carbon::now('UTC');
                $currentDate = '';

                if ($leavePeriod === 'future') {
                    $currentDate = $dateObj->modify('+1 year')->format('Y-m-d');
                } else if ($leavePeriod === 'past') {
                    $currentDate = $dateObj->modify('-1 year')->format('Y-m-d');
                } else {
                    $currentDate = $dateObj->format('Y-m-d');
                }
                // $whereQuery .= " AND leaveEntitlement.employeeId = " . $employeeId;
                $whereQuery = $whereQuery . " AND leaveEntitlement.leavePeriodFrom <= " . "'$currentDate'" . " AND leaveEntitlement.leavePeriodTo >= " . "'$currentDate'";
            }

            if ($dataType === 'table') {
                if ($tableOptions->pageNo && $tableOptions->pageCount) {
                    $skip = ($tableOptions->pageNo - 1) * $tableOptions->pageCount;
                    $paginationQuery = "LIMIT " . $skip . ", " . $tableOptions->pageCount;
                }
               
                if(isset($tableOptions->sort) && $tableOptions->sort !== null) {
                   if ($tableOptions->sort->name === 'type') {
                      $orderQuery = "ORDER BY type " . $tableOptions->sort->order;
                    } else if ($tableOptions->sort->name === 'leavePeriod') {
                       $orderQuery = "ORDER BY leavePeriod " . $tableOptions->sort->order;
                    } else if ($tableOptions->sort->name === 'usedCount') {
                       $orderQuery = "ORDER BY usedCount " . $tableOptions->sort->order;
                    }
                }
            }

            $query = "
            SELECT 
                leaveType.name,
                leavePeriodFrom,
                leavePeriodTo,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                CONCAT(leaveEntitlement.leavePeriodFrom, ' to ', leaveEntitlement.leavePeriodTo) as leavePeriod,
                sum(leaveEntitlement.entilementCount) as entitlementCount,
                sum(leaveEntitlement.pendingCount) as pendingCount,
                sum(leaveEntitlement.usedCount) as usedCount,
                sum(leaveEntitlement.entilementCount - leaveEntitlement.pendingCount - leaveEntitlement.usedCount) as leaveBalance
            FROM leaveEntitlement
                left join employee on employee.id = leaveEntitlement.employeeId 
                left join leaveType on leaveType.id = leaveEntitlement.leaveTypeId
            {$whereQuery}
            group by leaveEntitlement.employeeId, leaveEntitlement.leaveTypeId, leavePeriodFrom           
            {$orderQuery}
            {$paginationQuery}
            ;
            ";

            $leaveEntitlementUsage = DB::select($query);
            $leaveEntitlementUsage =collect($leaveEntitlementUsage)->sortBy('id');
            
            if ($dataType === 'table') {
                return $leaveEntitlementUsage;
            } else {
                $headerArray = ['Type','Leave Period', 'Entitlement Count', 'Pending Count', 'Used Count', 'Leave Balance'];
                $report ="employee";
                $fileData = $this->downloadLeaveEntitlement($headerArray, $leaveEntitlementUsage, 'A1:F1',$report);
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function getLeaveEntitlementUsageLeaveType($dataType, $tableOptions, $searchOptions)
    {
        try {
            $whereQuery = 'where';
            $paginationQuery = '';
            $orderQuery = '';
            $manySearch = false;
            $whereInQuery = '';

            // for filter only permitted employees results
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (count($permittedEmployeeIds) > 0) {
                $whereInQuery = "AND leaveEntitlement.employeeId IN (" . implode(",", $permittedEmployeeIds) . ")";

                if ($searchOptions->entityId) {
                    // $selectedFields = ['id', 'parentEntityId'];
                    // $nodes = DB::table("orgEntity")->where('isDelete', false)->orderBy("parentEntityId", "DESC")->get($selectedFields)->toArray();
                    // $entities = $this->findChildren($searchOptions->entityId, $nodes, []);
                    // $entityIds = array_values($entities);
                    // array_push($entityIds, $searchOptions->entityId);
                    $entityIds = $this->getParentEntityRelatedChildNodes((int)$searchOptions->entityId);
                    array_push($entityIds, (int)$searchOptions->entityId);


                    $whereInQuery .= " AND employeeJob.orgStructureEntityId IN (" . implode(",", $entityIds) .")";
                }
            }

            if ($searchOptions->leaveType) {
                $whereQuery = $whereQuery . " leaveEntitlement.leaveTypeId = " . $searchOptions->leaveType;
                $manySearch = true;
            }

            if ($searchOptions->leavePeriod) {
                $dateObj = Carbon::now('UTC');
                $currentDate = '';

                if ($searchOptions->leavePeriod === 'future') {
                    $currentDate = $dateObj->modify('+1 year')->format('Y-m-d');
                } else if ($searchOptions->leavePeriod === 'past') {
                    $currentDate = $dateObj->modify('-1 year')->format('Y-m-d');
                } else {
                    // current
                    $currentDate = $dateObj->format('Y-m-d');
                }

                $manySearch ? $whereQuery = $whereQuery . ' and' : null;
                $whereQuery = $whereQuery . " leaveEntitlement.leavePeriodFrom <= " . "'$currentDate'" . " and leaveEntitlement.leavePeriodTo >= " . "'$currentDate'";
                $manySearch = true;
            }
            if ($searchOptions->activeState === "false") {
                $manySearch ? $whereQuery = $whereQuery . ' and' : null;
                $whereQuery = $whereQuery . " employee.isActive = " . 'true';
                $manySearch = true;
            }
            // if ($searchOptions->jobTitle) {
            //     $manySearch ? $whereQuery = $whereQuery . ' and' : null;
            //     $whereQuery = $whereQuery . " jobTitle.id = " . $searchOptions->jobTitle;
            //     $manySearch = true;
            // }
            // if ($searchOptions->location) {
            //     $manySearch ? $whereQuery = $whereQuery . ' and' : null;
            //     $whereQuery = $whereQuery . " location.id = " . $searchOptions->location;
            //     $manySearch = true;
            // }
            // if ($searchOptions->department) {
            //     $manySearch ? $whereQuery = $whereQuery . ' and' : null;
            //     $whereQuery = $whereQuery . " department.id = " . $searchOptions->department;
            //     $manySearch = true;
            // }
            // if ($searchOptions->entityId != 1) {
            //     $manySearch ? $whereQuery = $whereQuery . ' and' : null;
            //     $whereQuery = $whereQuery . " department.id = " . $searchOptions->department;
            //     $manySearch = true;
            // }

            if ($dataType === 'table') {
                if ($tableOptions->pageNo && $tableOptions->pageCount) {
                    $skip = ($tableOptions->pageNo - 1) * $tableOptions->pageCount;
                    $paginationQuery = "LIMIT " . $skip . ", " . $tableOptions->pageCount;
                }
                if(isset($tableOptions->sort) && $tableOptions->sort !== null) {
                    if ($tableOptions->sort->name === 'type') {
                       $orderQuery = "ORDER BY type " . $tableOptions->sort->order;
                    } else if ($tableOptions->sort->name === 'leavePeriod') {
                       $orderQuery = "ORDER BY leavePeriod " . $tableOptions->sort->order;
                    } else if ($tableOptions->sort->name === 'usedCount') {
                       $orderQuery = "ORDER BY usedCount " . $tableOptions->sort->order;
                    } else if ($tableOptions->sort->name === 'employeeName') {
                        $orderQuery = "ORDER BY employeeName " . $tableOptions->sort->order;
                     }
                }
            }

            $query = "
            SELECT 
                leaveEntitlement.leaveTypeId,
                leaveEntitlement.employeeId,
                leaveType.name,
                leavePeriodFrom,
                leavePeriodTo,
                employee.employeeNumber as employeeNumber,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                CONCAT(leaveEntitlement.leavePeriodFrom, ' to ', leaveEntitlement.leavePeriodTo) as leavePeriod,
                sum(leaveEntitlement.entilementCount) as entitlementCount,
                sum(leaveEntitlement.pendingCount) as pendingCount,
                sum(leaveEntitlement.usedCount) as usedCount,
                sum(leaveEntitlement.entilementCount - leaveEntitlement.pendingCount - leaveEntitlement.usedCount) as leaveBalance 
            FROM leaveEntitlement
                left join employee on employee.id = leaveEntitlement.employeeId 
                left join employeeJob on employeeJob.id = employee.currentJobsId 
                left join leaveType on leaveType.id = leaveEntitlement.leaveTypeId
            {$whereQuery}
            {$whereInQuery}
            group by leaveEntitlement.employeeId, leaveEntitlement.leaveTypeId, leavePeriodFrom
            
            {$orderQuery}
            {$paginationQuery}
            ;
            ";

            $leaveEntitlementUsage = DB::select($query);
            // $leaveEntitlementUsage =collect($leaveEntitlementUsage)->sortBy('id');

            if ($dataType === 'table') {
                return $leaveEntitlementUsage;
            } else {
                $headerArray = ['Employee Number', 'Employee Name', 'Type','Leave Period', 'Entitlement Count', 'Pending Count', 'Used Count', 'Leave Balance'];
                $report ="leaveType";
                $fileData = $this->downloadLeaveEntitlement($headerArray, $leaveEntitlementUsage, 'A1:H1' ,$report);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function getLeaveEntitlementUsageForLeaveEntitlement($dataType, $tableOptions, $searchOptions)
    {
        try {
    
            //grab related employees
            $relatedEmployees = $this->store->getFacade()::table('employee')
                ->selectRaw("employee.id,
                    CONCAT_WS(' ', employee.firstName,  employee.lastName) AS employeeName,
                    employee.employeeNumber")
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.isDelete', false);


            // for filter only permitted employees results
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (count($permittedEmployeeIds) > 0) {
                $relatedEmployees = $relatedEmployees->whereIn('employee.id', $permittedEmployeeIds);

                if ($searchOptions->entityId) {
                    $entityIds = $this->getParentEntityRelatedChildNodes((int)$searchOptions->entityId);
                    array_push($entityIds, (int)$searchOptions->entityId);

                    $relatedEmployees = $relatedEmployees->whereIn('employeeJob.orgStructureEntityId', $entityIds);
                }
            }

            if ($searchOptions->activeState === "false") {

                $relatedEmployees = $relatedEmployees->where('employee.isActive', true);
            }

            

            if ($dataType === 'table') {
                if(isset($tableOptions->sort) && $tableOptions->sort !== null) {
                    if ($tableOptions->sort->name === 'employeeName') {
                        $relatedEmployees = $relatedEmployees->orderBy("employeeName", $tableOptions->sort->order);
                    } elseif ($tableOptions->sort->name === 'employeeNumber') {
                        $relatedEmployees = $relatedEmployees->orderBy("employee.employeeNumber", $tableOptions->sort->order);
                    }
                }
                if ($tableOptions->pageNo && $tableOptions->pageCount) {
                    $skip = ($tableOptions->pageNo - 1) * $tableOptions->pageCount;
                    $relatedEmployees = $relatedEmployees->skip($skip)->take($tableOptions->pageCount);
                }
            }

            $employees = $relatedEmployees->get();
           
        
            //arrange leave type object
            $relatedLeaveTypes = $this->store->getFacade()::table('leaveType')->where('isDelete', false)->whereIn('id', $searchOptions->leaveTypes)->get();
            $processedLeaveTypes = [];
            if (!is_null($relatedLeaveTypes)) {
                foreach ($relatedLeaveTypes as $leaveTypeKey => $leaveType) {
                    $leaveType = (array) $leaveType;
                    $indexKey = 'leaveType-'.$leaveType['id'];
                    $tempObj = [
                        'allocated' => 0,
                        'approved' => 0,
                        'pending' => 0,
                        'balance' => 0
                    ];
                    $processedLeaveTypes[$indexKey] = $tempObj;
                }
            }
            $processedEmployees = [];
            $selectedEmpIds = [];

            foreach ($employees as $empkey => $employee) {
                $empIndexKey = 'emp-'.$employee->id;
                $obj = $employee;
                $obj->leaveTypeDetails = $processedLeaveTypes;
                $processedEmployees[$empIndexKey] = $obj;
                $selectedEmpIds[]= $employee->id; 
            }


            $relatedEntitlements = $this->store->getFacade()::table('leaveEntitlement')
            ->selectRaw("leaveEntitlement.leaveTypeId, 
            leaveEntitlement.employeeId, 
            leaveEntitlement.leavePeriodFrom, 
            leaveEntitlement.leavePeriodTo, 
            sum(leaveEntitlement.pendingCount) as pendingCount, 
            sum(leaveEntitlement.usedCount) as usedCount, 
            sum(leaveEntitlement.entilementCount) as entitlementCount, 
            sum(leaveEntitlement.entilementCount - leaveEntitlement.pendingCount - leaveEntitlement.usedCount) as leaveBalance")->where('leaveEntitlement.isDelete', false);

            if (!empty($searchOptions->leaveTypes)) {
                $relatedEntitlements = $relatedEntitlements->whereIn('leaveEntitlement.leaveTypeId', $searchOptions->leaveTypes);
            }
            if (!empty($selectedEmpIds)) {
                $relatedEntitlements = $relatedEntitlements->whereIn('leaveEntitlement.employeeId', $selectedEmpIds);
            }

            if ($searchOptions->leavePeriod) {
                $dateObj = Carbon::now('UTC');
                $currentDate = '';

                if ($searchOptions->leavePeriod === 'future') {
                    $currentDate = $dateObj->modify('+1 year')->format('Y-m-d');
                } else if ($searchOptions->leavePeriod === 'past') {
                    $currentDate = $dateObj->modify('-1 year')->format('Y-m-d');
                } else {
                    // current
                    $currentDate = $dateObj->format('Y-m-d');
                }

                $relatedEntitlements = $relatedEntitlements->where('leaveEntitlement.leavePeriodFrom','<=',$currentDate)->where('leaveEntitlement.leavePeriodTo', '>=', $currentDate);
                
            }
            
            $relatedEntitlements = $relatedEntitlements->groupBy('leaveEntitlement.employeeId', 'leaveEntitlement.leaveTypeId', 'leaveEntitlement.leavePeriodFrom')->get();
            
    
            if (!is_null($relatedEntitlements)) {
                foreach ($relatedEntitlements as $entitleKey => $entitlement) {
                    $entitlement = (array) $entitlement;
                    $relatedEmpKey = 'emp-'.$entitlement['employeeId'];
                    $relatedLeaveTypeKey = 'leaveType-'.$entitlement['leaveTypeId'];

                    $processedEmployees[$relatedEmpKey]->leaveTypeDetails[$relatedLeaveTypeKey]['allocated'] += $entitlement['entitlementCount'];
                    $processedEmployees[$relatedEmpKey]->leaveTypeDetails[$relatedLeaveTypeKey]['approved'] += $entitlement['usedCount'];
                    $processedEmployees[$relatedEmpKey]->leaveTypeDetails[$relatedLeaveTypeKey]['pending'] += $entitlement['pendingCount'];
                    $processedEmployees[$relatedEmpKey]->leaveTypeDetails[$relatedLeaveTypeKey]['balance'] += $entitlement['leaveBalance'];                    
                }
            }


            $leaveEntitlementUsage = array_values($processedEmployees);

            if ($dataType === 'table') {
                return $leaveEntitlementUsage;
            } else {
                $headerArray = [];
                $report ="leaveEntitlement";
                $fileData = $this->downloadLeaveEntitlementReport($headerArray, $leaveEntitlementUsage, 'A1:H1' ,$report, $searchOptions->leaveTypes);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getParentEntityRelatedChildNodes($id)
    {

        $items = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get();
        $kids = [];
        foreach ($items as $key => $item) {
            $item = (array) $item;
            if ($item['parentEntityId'] === $id) {
                $kids[] = $item['id'];
                array_push($kids, ...$this->getParentEntityRelatedChildNodes($item['id'], $items));
            }
        }

        return $kids;
    }

    private function downloadLeaveEntitlementReport($headerArray, $dataSetArray, $headerColumnCellRange ,$report, $leaveTypes)
    {
        try {
            $excelData = Excel::download(new LeaveEntitlementReportExcelExport($headerArray, $dataSetArray, $headerColumnCellRange,$report, $this->store, $leaveTypes), 'leaveEntitlementData.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return base64_encode($fileData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function downloadLeaveRequestReport($headerArray, $dataSetArray, $headerColumnCellRange ,$report)
    {
        try {
            $excelData = Excel::download(new ExcelExport($headerArray, $dataSetArray, $headerColumnCellRange,$report), 'leaveEntitlementData.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return base64_encode($fileData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function downloadLeaveEntitlement($headerArray, $dataSetArray, $headerColumnCellRange ,$report)
    {
        try {
            $excelData = Excel::download(new ExcelExport($headerArray, $dataSetArray, $headerColumnCellRange,$report), 'leaveEntitlementData.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return base64_encode($fileData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function calculateWorkingDaysCountForLeave($leaveTypeId, $fromDate, $toDate, $employeeId = null) {
        try {
            if (is_null($employeeId)) {
                $employeeId = $this->session->getUser()->employeeId;
            }

            $employeeCalendar = $this->workCalendarService->getEmployeeCalendar($employeeId);
            $leaveType = $this->leaveTypeService->getLeaveType($leaveTypeId)['data'];
            $leaveType->workingDayIds = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
                ->where('leaveTypeId', $leaveTypeId)
                ->get();

            $startDate = new DateTime($fromDate);
            $interval = new DateInterval('P1D');
            $endDate = new DateTime($toDate);
            $endDate = $endDate->add($interval);
            $period = new DatePeriod(
                $startDate,
                $interval,
                $endDate
            );

            $noOfWorkingDays = 0;
            foreach ($period as $key => $value) {
                $shift = $this->getShiftIfWorkingDay($leaveType, $employeeId, $employeeCalendar, $value);

                if (!empty($shift) && $shift->noOfDay > 0) {
                    $noOfWorkingDays += $shift->noOfDay;
                }
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_ADD_COMMENT'), ['count' => $noOfWorkingDays]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getShiftDataForLeaveDate($fromDate, $toDate, $employeeId = null) {
        try {
            if (is_null($employeeId)) {
                $employeeId = $this->session->getUser()->employeeId;
            }

            $startDate = new DateTime($fromDate);
            $interval = new DateInterval('P1D');
            $endDate = new DateTime($toDate);
            $endDate = $endDate->add($interval);
            $period = new DatePeriod(
                $startDate,
                $interval,
                $endDate
            );

            $noOfWorkingDays = 0;
            $shiftData = null;
            foreach ($period as $dateObject) {
                $shiftData = $this->getEmployeeWorkShift($employeeId, $dateObject);

                if (!is_null($shiftData)) {
                    $shiftData->short_leave_duration = $this->getConfigValue('short_leave_duration');
                }
               
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_ADD_COMMENT'), ['shift' => $shiftData]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    public function checkShortLeaveAccessabilityForCompany() {
        try {
            $companyMantainShortLeaveState = $this->getConfigValue('short_leave_maintain_state');

            $response = [
                'isMaintainShortLeave' => ($companyMantainShortLeaveState) ? true : false
            ];

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'),  $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function exportManagerLeaves($request)
    {
        try {
            $employeeId = (!empty($request['employee'])) ? $request['employee'] : null;
            $location = (!empty($request['location'])) ? $request['location'] : null;
            $fromDate = (!empty($request['fromDate'])) ? $request['fromDate'] : null;
            $toDate = (!empty($request['toDate'])) ? $request['toDate'] : null;
            $pageNo = (!empty($request['pageNo'])) ? $request['pageNo'] : null;
            $pageCount = (!empty($request['pageCount'])) ? $request['pageCount'] : null;
            $sort = (!empty($request['sort'])) ? json_decode($request['sort']) : null;
            $leaveType = (!empty($request['leaveType'])) ? $request['leaveType'] : null;
            $department = (!empty($request['department'])) ? $request['department'] : null;
            $status = (!empty($request['status'])) ? json_decode($request['status']) : null;
            $accessType = 'manager';
            $isWithInactiveEmployees = (!empty($request['isWithInactiveEmployees'])) ? $request['isWithInactiveEmployees'] : null;
            $filter = (!empty($request['filter'])) ? $request['filter'] : null;
            $filter = json_decode($filter);

            if (empty($employeeId)) {
                $employeeId = $this->session->getContext()->getManagerPermittedEmployeeIds();
            }
 
            $leaveRequests = $this->getLeaveRequestData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $location, $accessType, $department,$isWithInactiveEmployees, $filter);
            $headerColumnCellRange = 'A1:F1';
            $headerArray = ['Employee','Start Date', 'End Date', 'Leave Type', 'Requested Days', 'Status'];
            $excelData = Excel::download(new LeaveRequestExcelExport($headerArray,  $leaveRequests->sheets, $headerColumnCellRange), 'leaveRequestData.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function exportAdminLeaves($request)
    {
        try {
            $employeeId = (!empty($request['employee'])) ? $request['employee'] : null;
            $location = (!empty($request['location'])) ? $request['location'] : null;
            $fromDate = (!empty($request['fromDate'])) ? $request['fromDate'] : null;
            $toDate = (!empty($request['toDate'])) ? $request['toDate'] : null;
            $pageNo = (!empty($request['pageNo'])) ? $request['pageNo'] : null;
            $pageCount = (!empty($request['pageCount'])) ? $request['pageCount'] : null;
            $sort = (!empty($request['sort'])) ? json_decode($request['sort']) : null;
            $leaveType = (!empty($request['leaveType'])) ? $request['leaveType'] : null;
            $status = (!empty($request['status'])) ? json_decode($request['status']) : null;
            $requestedUser = $this->session->getUser();
            $roleId = $requestedUser->adminRoleId;
            $userRole   = $this->redis->getUserRole($roleId);
            $scopeOfAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $scopeOfAccess = (array) $scopeOfAccess;
            $accessType = 'admin';
            $department = (!empty($request['department'])) ? $request['department'] : null;
            $isWithInactiveEmployees = (!empty($request['isWithInactiveEmployees'])) ? $request['isWithInactiveEmployees'] : null;
            $filter = (!empty($request['filter'])) ? $request['filter'] : null;
            $filter = json_decode($filter);

            if (empty($employeeId)) {
                $employeeId = $this->session->getContext()->getAdminPermittedEmployeeIds();
            }

            if (empty($location)) {
               $location = (!empty($scopeOfAccess['location'])) ? $scopeOfAccess['location'] : [];
            }
        
            $leaveRequests = $this->getLeaveRequestData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $leaveType, $status, $location, $accessType, $department, $isWithInactiveEmployees, $filter);
            $headerColumnCellRange = 'A1:F1';
            $headerArray = ['Employee','Start Date', 'End Date', 'Leave Type', 'Requested Days', 'Status'];
            $excelData = Excel::download(new LeaveRequestExcelExport($headerArray,  $leaveRequests->sheets, $headerColumnCellRange), 'leaveRequestData.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    /**
     * Following function retrives leave history of all employee that relate to particular admin.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All leaves history retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAdminLeavesHistory($request)
    {
        try {
            $employeeId = $request->query('employee', null);
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

            $leaveRequests = $this->getLeaveRequestHistoryData($employeeId, $pageNo, $pageCount, $sort, $accessType, $filter, $searchString);
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function retrives leave history of particular employee.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All leaves history retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getEmployeeLeavesHistory($request)
    {
        try {
            $employeeId = $this->session->getEmployee()->id;
            
            if (!$employeeId) {
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_GET'));
            }
            
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $accessType = 'employee';
            $filter = $request->query('filter', null);
            $filter = json_decode($filter);
            $searchString = $request->query('searchString', null);
            
            $leaveRequests = $this->getLeaveRequestHistoryData($employeeId, $pageNo, $pageCount, $sort, $accessType, $filter, $searchString);
            
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET'), $leaveRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function retrives leave history for employees.
     *
     * @return int | String | array
     *
     * Sample output:
     * ["count" => 15, "Sheets" =>[{"title": "LK HR", ...}, ...]]
     * 
     */
    private function getLeaveRequestHistoryData($employeeId, $pageNo, $pageCount, $sort, $accessType, $filter, $searchString)
    {
        try {

            $allSucessStates = [2];
            $allFailiureStates = [3,4];

            $leaveRequests = DB::table('leaveRequest')
            ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'leaveRequest.workflowInstanceId')
            ->leftJoin('employee', 'employee.id', '=', 'leaveRequest.employeeId')
            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->leftJoin('location', 'location.id', '=', 'employeeJob.locationId')
            ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
            ->leftJoin('leaveType', 'leaveType.id', '=', 'leaveRequest.leaveTypeId')
            ->leftJoin('workflowState', 'workflowState.id', '=', 'leaveRequest.currentState')
            ->selectRaw("CONCAT_WS(' ', firstName,  lastName) AS employeeName, employee.firstName, employee.lastName, employee.id as employeeIdNo")
            ->selectRaw('leaveRequest.id,
                leaveRequest.numberOfLeaveDates,
                leaveRequest.fromDate,
                leaveRequest.comments,
                leaveRequest.toDate,
                leaveRequest.fromTime,
                leaveRequest.toTime,
                leaveRequest.createdAt as requestedDate,
                leaveRequest.reason,
                leaveRequest.leaveTypeId,
                leaveRequest.currentState')
            ->selectRaw('employeeJob.locationId,
                location.name as locationName,
                department.name as departmentName,leaveType.name as leaveTypeName,
                leaveType.leaveTypeColor as leaveTypeColor,
                workflowInstance.currentStateId,
                workflowInstance.workflowId,
                workflowInstance.id as workflowInstanceIdNo,
                workflowState.label as StateLabel');

            if (is_array($employeeId)) {
                $leaveRequests = $leaveRequests->whereIn('leaveRequest.employeeId', $employeeId);
            } else {
                $leaveRequests = $leaveRequests->where('leaveRequest.employeeId', $employeeId);
            }

            if (!empty($filter->leaveTypeId)) {
                $searchArr = $filter->leaveTypeId;
                $leaveRequests = $leaveRequests->whereIn('leaveType.id', $searchArr);
            }

            if (!empty($filter->StateLabel)) {
                $searchArr = $filter->StateLabel;
                $successAndFailiureStatus = array_merge($allSucessStates, $allFailiureStates);
                $pendingStates = [1];
                
                $filterStates = [];

                foreach ($searchArr as $key => $value) {
                    switch ($value) {
                        case 'Pending':
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, [1]) : [1]; 
                            break;
                        case 'Approved':
                            $filterStates = (sizeof($filterStates) > 0 ) ?  array_merge($filterStates, [2]) : [2]; 
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
                $leaveRequests = $leaveRequests->whereIn('leaveRequest.currentState', $filterStates);
            }

            if (!empty($searchString)) {
                $leaveRequests = $leaveRequests->where('leaveRequest.fromDate', 'like', '%' . $searchString . '%');
            }
            $leaveRequestCount = $leaveRequests->count();


            if (empty($sort)) {
                $leaveRequests = $leaveRequests->orderBy("leaveRequest.fromDate", 'DESC');
            } else {
                if (!empty($sort->name) && !empty($sort->order)) {

                    switch ($sort->name) {
                        case 'employeeName':
                            $leaveRequests = $leaveRequests->orderBy("employee.firstName", $sort->order);
                            break;
                        case 'fromDate':
                            $leaveRequests = $leaveRequests->orderBy("leaveRequest.fromDate", $sort->order);
                            break;
                        case 'toDate':
                            $leaveRequests = $leaveRequests->orderBy("leaveRequest.toDate", $sort->order);
                            break;
                        case 'id':
                            $leaveRequests = $leaveRequests->orderBy("leaveRequest.id", $sort->order);
                            break;
                        default:
                            $leaveRequests = $leaveRequests->orderBy("leaveRequest.fromDate", $sort->order);
                            break;
                    }
                } else {
                    $leaveRequests = $leaveRequests->orderBy("leaveRequest.fromDate", 'DESC');
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
                array_push($leaveRequestsArray, $leave);
            }
            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;

            foreach ($leaveRequestsArray as $leaveRequestIndex => $leaveRequest) {
                $leaveRequest = (array) $leaveRequest;

                $leaveRequestsArray[$leaveRequestIndex]['approvedBy'] = '-';
                $leaveRequestsArray[$leaveRequestIndex]['approvedAt'] = '-';
                $leaveRequestsArray[$leaveRequestIndex]['canDirectCancelByAdmin'] = false;

                if (!empty($leaveRequest['workflowId'])) {

                    $workFlowData = DB::table('workflowDefine')
                    ->select('workflowDefine.id', 'workflowDefine.contextId')
                    ->where('workflowDefine.id', '=', $leaveRequest['workflowId'])
                    ->where('workflowDefine.isDelete', '=', false)
                        ->first();

                    $workFlowData = (array) $workFlowData;
                    $successStates = [2];

                    // if (in_array($leaveRequest['currentState'], $successStates)) {
                    //     $performActionData = DB::table('workflowInstanceDetail')
                    //     ->leftJoin('workflowAction', 'workflowAction.id', '=', 'workflowInstanceDetail.actionId')
                    //     ->leftJoin('user', 'user.id', '=', 'workflowInstanceDetail.performUserId')
                    //     ->where('workflowInstanceDetail.instanceId', $leaveRequest['workflowInstanceIdNo'])
                    //     ->where('workflowInstanceDetail.postState', $leaveRequest['currentState'])
                    //     ->first();
                    //     if (!is_null($performActionData)) {
                    //         $performActionData = (array) $performActionData;
                    //         $leaveRequestsArray[$leaveRequestIndex]['approvedBy'] = $performActionData['firstName'] . ' ' . $performActionData['lastName'];
                    //         $leaveRequestsArray[$leaveRequestIndex]['approvedAt'] = $this->getFormattedDateForList($performActionData['updatedAt'], $timeZone);
                    //     }
                    // }
                } else {
                    if ($leaveRequest['currentState'] == 2) {
                        $leaveRequestsArray[$leaveRequestIndex]['canDirectCancelByAdmin'] = true;
                    }
                }

                $leaveRequestsArray[$leaveRequestIndex]['approvedBy'] = '';
                $leaveRequestsArray[$leaveRequestIndex]['approvedAt'] = '';

                $leaveRequestsArray[$leaveRequestIndex]['requestedDate'] = $this->getFormattedDateForList($leaveRequest['requestedDate'], $timeZone);
                $leaveRequestsArray[$leaveRequestIndex]['fromDate'] = Carbon::parse($leaveRequest['fromDate'])->format('d-m-Y');
                $leaveRequestsArray[$leaveRequestIndex]['toDate'] = Carbon::parse($leaveRequest['toDate'])->format('d-m-Y');


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
     * Following function return the formatted date type for given date.
     *
     * @return | string
     *
     * Sample output: '2022-09-01 5:25 PM'
     */
    private function getFormattedDateForList($date, $timeZone)
    {
        try {
            $formattedDate = '-';
            if (!empty($date) && $date !== '-' && $date !== '0000-00-00 00:00:00') {

                $formattedDate = Carbon::parse($date, 'UTC')->copy()->tz($timeZone);
                try {
                } catch (\Exception $e) {
                    echo 'invalid date';
                }

                $approvedAtArr = explode(' ', $formattedDate);
                if (!empty($approvedAtArr)) {
                    $formattedTime = Carbon::parse($approvedAtArr[1]);

                    $formattedDate = Carbon::parse($approvedAtArr[0])->format('d-m-Y'). ' at ' . $formattedTime->format('g:i A');
                }
            }

            return $formattedDate;
        } catch (\Exception $e) {
            echo 'invalid date';
        }
        
    }

    /**
     * Following function get leave covering person requests.
     *
     * @param $queryParams list related queryn params
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"    
     *      $data => [{id: 25...}] 
     */
    public function getLeaveCoveringRequests($queryParams)
    {
        try {
            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $pageNo = (!empty($queryParams['pageNo'])) ? $queryParams['pageNo'] : null;
            $pageCount = (!empty($queryParams['pageCount'])) ? $queryParams['pageCount'] : null;
            $state = (!empty($queryParams['stateName'])) ? $queryParams['stateName'] : 'All';

            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;


            $leaveCoveringRequests = DB::table('leaveCoveringPersonRequests')
            ->select(
                'leaveCoveringPersonRequests.*',
                'employee.firstName',
                'employee.lastName',
                'location.name as locationName',
                'department.name as departmentName',
                'division.name as divisionName',
                'jobTitle.name as jobTitleName'
            )
                ->leftJoin('leaveRequest', 'leaveRequest.id', '=', 'leaveCoveringPersonRequests.leaveRequestId')
                ->leftJoin('employee', 'employee.id', '=', 'leaveRequest.employeeId')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->leftJoin('location', 'location.id', '=', 'employeeJob.locationId')
                ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
                ->leftJoin('division', 'division.id', '=', 'employeeJob.divisionId')
                ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
                ->where('leaveCoveringPersonRequests.coveringEmployeeId', '=', $requestedEmployeeId)
                ->where('leaveCoveringPersonRequests.isDelete', '=', false);

            if ($state != 'All') {
                $leaveCoveringRequests = $leaveCoveringRequests->where('leaveCoveringPersonRequests.state', $state);
            }

            if ($pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $leaveCoveringRequests = $leaveCoveringRequests->skip($skip)->take($pageCount);
            }

            $leaveCoveringRequests = $leaveCoveringRequests->orderBy("leaveCoveringPersonRequests.createdAt", 'DESC');

            $leaveCoveringRequests = $leaveCoveringRequests->get();

            foreach ($leaveCoveringRequests as $key => $value) {

                $value = (array) $value;
                // $workflowId = $value['workflowId'];
                $leaveRequestModel = $this->getModel('leaveRequest', true);
                $leaveData = (array)$this->store->getById($leaveRequestModel, $value['leaveRequestId']);
                $details = (array) $leaveData;
                $leaveCoveringRequests[$key]->leaveData = $details;
                $leaveCoveringRequests[$key]->requestedOn = $this->getFormattedDateForList($value['createdAt'], $timeZone);

                switch ($value['state']) {
                    case 'APPROVED':
                        $leaveCoveringRequests[$key]->stateTagColor = 'green';
                        $leaveCoveringRequests[$key]->stateLabel = 'Approved';
                        break;
                    case 'DECLINED':
                        $leaveCoveringRequests[$key]->stateTagColor = 'red';
                        $leaveCoveringRequests[$key]->stateLabel = 'Declined';
                        break;
                    case 'PENDING':
                        $leaveCoveringRequests[$key]->stateTagColor = 'orange';
                        $leaveCoveringRequests[$key]->stateLabel = 'Pending';
                        break;
                    case 'CANCELED':
                        $leaveCoveringRequests[$key]->stateTagColor = 'geekblue';
                        $leaveCoveringRequests[$key]->stateLabel = 'Canceled';
                        break;

                    default:
                        $leaveCoveringRequests[$key]->stateTagColor = 'geekblue';
                        break;
                }

                // set display heading 1
                $leaveCoveringRequests[$key]->displayHeading1 = $this->generateHeading1($details);
                $leaveCoveringRequests[$key]->displayHeading2 = $this->generateHeading2($details);

                $leaveCoveringRequests[$key]->updatedAt =  $this->getFormattedDateForList($value['updatedAt'], $timeZone);
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_ALL_RETRIVE'), $leaveCoveringRequests);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveRequestMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function generate main heading discription of leave covering rquest
     *
     */
    private function generateHeading1($details)
    {
        $details = (array) $details;
        $heading = '';
        //get related leave Type
        $leaveTypeModel =  $this->getModel('leaveType', true);
        $leaveTypeData = (array) $this->store->getById($leaveTypeModel, $details['leaveTypeId']);
        $numberOfDays = (!empty($details['numberOfLeaveDates'])) ? $details['numberOfLeaveDates'] : '-';

        $heading = 'Type :' . $leaveTypeData['name'] . ' | Start Date : ' . date('d-m-Y', strtotime($details['fromDate'])) . ' | End Date : ' . date('d-m-Y', strtotime($details['toDate'])) . ' | Days : ' . $numberOfDays;

        return $heading;
    }

    /**
     * Following function generate sub heading discription of leave covering rquest
     *
     */
    private function generateHeading2($details)
    {
        $details = (array) $details;
        $heading2 = '';

        $heading2 = (!empty($details['reason'])) ? 'Reason : ' . $details['reason'] : 'Reason : -';

        return $heading2;
    }

    /**
     * Following function update leave covering person state.
     *
     * @param $coveringRequestData array containing leave covering request data
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"     
     */
    public function updateLeaveCoveringPersonRequest($coveringRequestData)
    {
        try {

            DB::beginTransaction();
            $existingCoveringRequest = $this->store->getById($this->leaveCoveringPersonRequestModel, $coveringRequestData['coveringRequestId']);
            if (is_null($existingCoveringRequest)) {
                DB::rollback();
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }

            $existingCoveringRequest = (array) $existingCoveringRequest;
            $coveringRequestUpdated['comment'] = $coveringRequestData['coveringPersonComment'];

            $existingLeave = $this->store->getById($this->leaveRequestModel, $existingCoveringRequest['leaveRequestId']);
            if (is_null($existingLeave)) {
                DB::rollback();
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave;
            $employeeId = $existingLeave['employeeId'];
            $responseMessage = null;

            if ($coveringRequestData['action'] == 'decline') {
                //need to reverse leave entitlements and cancel leave
                $coveringRequestUpdated['state'] = 'DECLINED';
                $responseMessage =  'leaveRequestMessages.basic.SUCC_DECLINED_REQUEST'; 

                // get leave request details
                $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
                ->where('leaveRequestId', $existingLeave['id'])
                ->get()->toArray();

                foreach ($leaveRequestDetails as $key => $value) {
                    $value = (array) $value;
                    if ($value['status'] == 'PENDING' || $value['status'] == 'APPROVED') {
                        $leaveDates[] = $value['leaveDate'];
                    }
                }

                //update leave covering person request state and comment 
                $no = $this->store->updateById($this->leaveCoveringPersonRequestModel, $existingCoveringRequest['id'], $coveringRequestUpdated);

                //change the leave request current state as cancelled
                $leaveRequestUpdated['currentState'] = 4;
                $updateCurrentState = $this->store->updateById($this->leaveRequestModel, $existingLeave['id'], $leaveRequestUpdated);

                //reverse leave entitlement allocations
                $res = $this->reverseLeaveEntitlementAllocations($existingLeave['id'], 'CANCELED');

                if ($res['error']) {
                    DB::rollback();
                    return $this->error($res['statusCode'], $res['message'], $$id);
                }

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $leaveDates
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));
            } elseif ($coveringRequestData['action'] == 'accept') {
                // update leave covering requests state and comment
                $coveringRequestUpdated['state'] = 'APPROVED';
                $responseMessage =  'leaveRequestMessages.basic.SUCC_APPROVED_REQUEST'; 
                $no = $this->store->updateById($this->leaveCoveringPersonRequestModel, $existingCoveringRequest['id'], $coveringRequestUpdated);

                // this is the workflow context id related for Apply Leave
                $context = 2;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $existingLeave, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                $leaveRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $existingLeave['id'], $leaveRequstUpdated);
                if (!$updateLeaveRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $newLeave['id']);
                }
            }

            //send related email for particular employee

            //get leave request employee detail
            $requestEmpData =  DB::table('employee')->select('workEmail','firstName','lastName')
            ->where('employee.id','=',$existingLeave['employeeId'])->first();

            //get covering person employee detail
            $coveringEmpData =  DB::table('employee')->select('workEmail','firstName', 'lastName')
            ->where('employee.id','=',$existingCoveringRequest['coveringEmployeeId'])->first();

            $fromDate = $existingLeave['fromDate'];
            $toDate = $existingLeave['toDate'];
            $numOfLeaveDates = $existingLeave['numberOfLeaveDates'];
            $dayString = $existingLeave['numberOfLeaveDates'] == 1 ? 'Day.' : 'Days.';
            $covereingEmployeeName = $coveringEmpData->firstName.' '.$coveringEmpData->lastName;

            if ($coveringRequestData['action'] == 'accept') {
                $emailBody = 'The Leave covering request that related to the leave that you applied from '.$fromDate.' to '.$toDate.' for '.$numOfLeaveDates.' '.$dayString.' has been approved by '.$covereingEmployeeName;
            } else if ($coveringRequestData['action'] == 'decline') {
                $emailBody = 'The Leave covering request that related to the leave that you applied from '.$fromDate.' to '.$toDate.' for '.$numOfLeaveDates.' '.$dayString.' has been declined by '.$covereingEmployeeName;

            }

            //send email for covering person
            $newEmail =  dispatch(new EmailNotificationJob(new Email('emails.leaveCoveringPersonEmailContent', array($coveringEmpData->workEmail), "Leave Covering Request", array([]), array("receipientFirstName" => $requestEmpData->firstName, "emailBody" => $emailBody))))->onQueue('email-queue');

            DB::commit();
            return $this->success(200, Lang::get($responseMessage), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), null);
        }
    }


    /**
     * Following function cancel leave covering person request.
     *
     * @param $coveringRequestData array containing leave covering request data
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"     
     */
    public function cancelCoveringPersonBasedLeaveRequest($id)
    {
        try {
            DB::beginTransaction();
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                DB::rollback();
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave;
            $employeeId = $existingLeave['employeeId'];

            $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
            ->where('leaveRequestId', $existingLeave['id'])
            ->get()->toArray();

            foreach ($leaveRequestDetails as $key => $value) {
                $value = (array) $value;
                if ($value['status'] == 'PENDING' || $value['status'] == 'APPROVED') {
                    $leaveDates[] = $value['leaveDate'];
                }
            }

            //check leave request has covering person
            $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
            ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $existingLeave['id'])
            ->where('leaveCoveringPersonRequests.state', '=', 'pending')
            ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                ->first();

            if (!is_null($coveringPersonRequestData)) {
                $coveringRequestUpdated['state'] = 'CANCELED';
                $no = $this->store->updateById($this->leaveCoveringPersonRequestModel, $coveringPersonRequestData->id, $coveringRequestUpdated);
            }

            $leaveRequestUpdated['currentState'] = 4;
            $updateCurrentState = $this->store->updateById($this->leaveRequestModel, $existingLeave['id'], $leaveRequestUpdated);

            $res = $this->reverseLeaveEntitlementAllocations($id, 'CANCELED');

            if ($res['error']) {
                DB::rollback();
                return $this->error($res['statusCode'], $res['message'], $$id);
            }

            $dataSet = [
                'employeeId' => $employeeId,
                'dates' => $leaveDates
            ];

            event(new AttendanceDateDetailChangedEvent($dataSet));

            DB::commit();
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_CANCEL'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_CANCEL'), null);
        }
    }


    /**
     * Following function cancel leave request.
     *
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"     
     */
    public function cancelLeaveRequestDates($id, $data)
    {
        try {
            DB::beginTransaction();

            $isAllowWf = ($data['isInInitialState']) ? false : true;
            $existingLeave = $this->store->getById($this->leaveRequestModel, $id);
            if (is_null($existingLeave)) {
                DB::rollback();
                return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingLeave = (array) $existingLeave;
            $employeeId = $existingLeave['employeeId'];

            $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
                ->where('leaveRequestId', $existingLeave['id'])
                ->whereIn('status', ['PENDING', 'APPROVED'])
                ->orderBy('leaveDate', 'asc')
                ->get()->toArray();

            $originalLeaveDates = [];
            $originalLeaveDatesIds = [];
            $cancelLeaveDates = [];
            $periodArr = [];
            $leaveDates = [];
            $leaveCancelRequestDetailIds = [];
            $newlyUpdatedLeaveDates = [];

            foreach ($leaveRequestDetails as $key => $value) {
                $value = (array) $value;
                $originalLeaveDates[$value['leaveDate']] = $value;
                $originalLeaveDatesIds[] = $value['id'];
                $leaveDates[] = $value['leaveDate'];
            }

            $isFullyCanceled = true;

            foreach ($data['leaveCancelDates'] as $cancelKey => $cancelDateDetails) {
                $cancelDateDetails = (array) $cancelDateDetails;
                $dateObj = new DateTime($cancelDateDetails['date']);

                if ($cancelDateDetails['isCheckedSecondHalf'] && $cancelDateDetails['isCheckedFirstHalf']) {
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['cancelLeavePeriodType'] = 'FULL_DAY';
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['cancelLeaveDate'] = $dateObj->format('Y-m-d');
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['orginalLeavePeriodType'] = $originalLeaveDates[$dateObj->format('Y-m-d')]['leavePeriodType'];
                } elseif (!$cancelDateDetails['isCheckedSecondHalf'] && $cancelDateDetails['isCheckedFirstHalf']) {
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['cancelLeavePeriodType'] = 'FIRST_HALF_DAY';

                    if ($originalLeaveDates[$dateObj->format('Y-m-d')]['leavePeriodType'] != 'FIRST_HALF_DAY') {
                        $isFullyCanceled = false;
                        array_push($periodArr, $dateObj);

                        $newlyUpdatedLeaveDates[$dateObj->format('Y-m-d')] = 'SECOND_HALF_DAY';
                    }

                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['cancelLeaveDate'] = $dateObj->format('Y-m-d');
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['orginalLeavePeriodType'] = $originalLeaveDates[$dateObj->format('Y-m-d')]['leavePeriodType'];
                } elseif ($cancelDateDetails['isCheckedSecondHalf'] && !$cancelDateDetails['isCheckedFirstHalf']) {
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['cancelLeavePeriodType'] = 'SECOND_HALF_DAY';
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['cancelLeaveDate'] = $dateObj->format('Y-m-d');
                    $cancelLeaveDates[$dateObj->format('Y-m-d')]['orginalLeavePeriodType'] = $originalLeaveDates[$dateObj->format('Y-m-d')]['leavePeriodType'];
                    if ($originalLeaveDates[$dateObj->format('Y-m-d')]['leavePeriodType'] != 'SECOND_HALF_DAY') {
                        $isFullyCanceled = false;
                        array_push($periodArr, $dateObj);
                        $newlyUpdatedLeaveDates[$dateObj->format('Y-m-d')] = 'FIRST_HALF_DAY';
                    }
                } else {
                    $isFullyCanceled = false;
                    array_push($periodArr, $dateObj);
                    $newlyUpdatedLeaveDates[$dateObj->format('Y-m-d')] = $originalLeaveDates[$dateObj->format('Y-m-d')]['leavePeriodType'];
                }
            }

            //save cancel leave request
            $cancelLeaveRequestData = [
                'leaveRequestId' => $id,
                'employeeId' => $employeeId,
                'cancelReason' => $data['leaveCancelReason'],
                'currentState' => 1
            ];
            $newCancelLeave = $this->store->insert($this->cancelLeaveRequestModel, $cancelLeaveRequestData, true);


            //save cancel leave details
            foreach ($cancelLeaveDates as $cancelLeaveDateIndex => $cancelLeaveDateData) {
                $cancelLeaveDateData = (array) $cancelLeaveDateData;
                $cancelLeaveDetail = [
                    'cancelLeaveRequestId' => $newCancelLeave['id'],
                    'cancelLeaveDate' => $cancelLeaveDateData['cancelLeaveDate'],
                    'status' => 'PENDING',
                    'cancelLeavePeriodType' => $cancelLeaveDateData['cancelLeavePeriodType']
                ];

                $newCancelLeaveDetail = $this->store->insert($this->cancelLeaveRequestDetailModel, $cancelLeaveDetail, true);
                if (!is_null($newCancelLeaveDetail)) {
                    $leaveCancelRequestDetailIds[] =  $newCancelLeaveDetail['id'];
                }
            }

            $leaveCancelType = ($isAllowWf) ? 'leaveCancelRequest' : 'leaveCancelationUpdate';
            
            //check whether self service reuest type is lock locked
            $hasLockedSelfService = $this->checkSelfServiceRecordLockIsEnable($leaveDates, $leaveCancelType);
            
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
                //need to handel workflow

                //check whether has pending or approved cancel leave requests for perticular leave
                $hasPreviousCancelRecord = $this->checkHasPendingOrApprovedCancelLeaveRequests($existingLeave);

                if ($hasPreviousCancelRecord) {
                    DB::rollBack();
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_HAS_PREVIOUS_CANCEL_REQUEST'), []);
                }


                $leaveCancelDataSet = (array) $this->store->getById($this->cancelLeaveRequestModel, $newCancelLeave['id']);
                if (is_null($leaveCancelDataSet)) {
                    DB::rollBack();
                    return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_CREATE'), $id);
                }

                $leaveCancelDataSet['cancelDatesDetails'] = $data['leaveCancelDates'];
                $leaveCancelDataSet['originalDatesDetails'] = $leaveRequestDetails;

                // this is the workflow context id related for Cancel Leave
                $context = 6;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $leaveCancelDataSet, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                $leaveRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateLeaveRequest = $this->store->updateById($this->cancelLeaveRequestModel, $newCancelLeave['id'], $leaveRequstUpdated);
                if (!$updateLeaveRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'), $newCancelLeave['id']);
                }
            } else {
                $instanceLevelSequence = 0;
                $this->runLeaveCancellationProcess($existingLeave, $data, $isFullyCanceled, $cancelLeaveDates, $originalLeaveDatesIds, $newlyUpdatedLeaveDates, $newCancelLeave['id'], $leaveCancelRequestDetailIds, $leaveDates, $instanceLevelSequence);
                //send email to leave covering person whether leave covering person is relate to the leave
                $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
                ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $existingLeave['id'])
                ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                ->first();
                
                if (!is_null($coveringPersonRequestData)) {
                    //get leave request employee detail
                    $requestEmpData =  DB::table('employee')->select('workEmail','firstName','lastName')
                    ->where('employee.id','=',$existingLeave['employeeId'])->first();

                    //get covering person employee detail
                    $coveringEmpData =  DB::table('employee')->select('workEmail','firstName')
                    ->where('employee.id','=',$coveringPersonRequestData->coveringEmployeeId)->first();

                    $fromDate = $existingLeave['fromDate'];
                    $toDate = $existingLeave['toDate'];
                    $numOfLeaveDates = $existingLeave['numberOfLeaveDates'];
                    $dayString = $numOfLeaveDates == 1 ? ' Day.' : ' Days.';
                    $requestEmployeeName = $requestEmpData->firstName.' '.$requestEmpData->lastName;

                    //set email body
                    $emailBody = "The Leave request of ".$requestEmployeeName." that applied from ".$fromDate." to ".$toDate." for ".$numOfLeaveDates.$dayString."Which that you assigned as the covering person has been canceled";

                    //send email for covering person
                    $newEmail =  dispatch(new EmailNotificationJob(new Email('emails.leaveCoveringPersonEmailContent', array($coveringEmpData->workEmail), "Leave Covering Request", array([]), array("receipientFirstName" => $coveringEmpData->firstName, "emailBody" => $emailBody))))->onQueue('email-queue');
                }
            
            }

            DB::commit();
            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_LEAVE_CANCEL'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_LEAVE_CANCEL'), null);
        }
    }

    /**
     * Following function check whether the leave request has pending or approved leave cancel requests     *
     * Sample output: boolean
     */
    private function checkHasPendingOrApprovedCancelLeaveRequests($leaveRequstData)
    {
        $leaveRequstData = (array) $leaveRequstData;
        //get all cancel leave request that go through workflow that realte to leave request id
        $cancelRequestData = DB::table('cancelLeaveRequest')
            ->where('cancelLeaveRequest.leaveRequestId', '=', $leaveRequstData['id'])
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

    /**
     * Following function check whether the leave request is fully approved leave request
     * Sample output: boolean
     */
    private function checkLeaveIsFullyApprovedOne($leaveRequstData)
    {
        $leaveRequstData = (array) $leaveRequstData;
        if (!empty($leaveRequstData['workflowInstanceId'])) {

            //get all cancel leave request that go through workflow that realte to leave request id
            $approvedLeaveRequestData = DB::table('workflowInstance')
                ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                ->where('workflowInstance.id', '=', $leaveRequstData['workflowInstanceId'])
                ->where('workflowInstance.currentStateId', '=', 2)
                // ->whereJsonContains('workflowDefine.sucessStates', $leaveRequstData['currentState'])
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
     * Following function run the leave cancellation process
     */
    public function runLeaveCancellationProcess($existingLeave, $data, $isFullyCanceled, $cancelLeaveDates, $originalLeaveDatesIds, $newlyUpdatedLeaveDates, $cancelLeaveRequestId, $leaveCancelRequestDetailIds, $leaveDates, $instanceLevelSequence)
    {
        $employeeId = $existingLeave['employeeId'];
        $id = $existingLeave['id'];
        $relateUser =  DB::table('user')->where('employeeId', $employeeId)->where('isDelete', false)->first();
        $isFullyApprovedLeave = $this->checkLeaveIsFullyApprovedOne($existingLeave);

        // if ($data['isInInitialState']) {
        if ($isFullyCanceled) {

            //reverse all entitlement allocations related to leave
            $res = $this->reverseLeaveEntitlementAllocations($id, 'CANCELED', $isFullyApprovedLeave);
            if ($res['error']) {
                DB::rollback();
                return $this->error($res['statusCode'], $res['message'], $id);
            }

            //change the leave request current state as cancelled
            $leaveRequestUpdated['currentState'] = 4;
            $updateCurrentState = $this->store->updateById($this->leaveRequestModel, $existingLeave['id'], $leaveRequestUpdated);

            if (!empty($existingLeave['workflowInstanceId'])) {
                //change workflow instance prior state to cancel
                $workflowInstanceUpdated['currentStateId'] = 4;
                $updatePriorState = $this->store->updateById($this->workflowInstanceModel, $existingLeave['workflowInstanceId'], $workflowInstanceUpdated);


                // get instance relate approval level
                $instanceAprovalLevelData = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $existingLeave['workflowInstanceId'])->where('levelSequence', $instanceLevelSequence)->first();
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
        } else {
            //handle when it is partially cancelled

            //reverse all entitlement allocations related to leave
            $res = $this->reverseLeaveEntitlementAllocations($id, 'CANCELED', $isFullyApprovedLeave, true);
            if ($res['error']) {
                DB::rollback();
                return $this->error($res['statusCode'], $res['message'], $id);
            }

            $newlyAddedDetailRecordsIds = [];
            $cancelLeaveDetailsIds = [];

            foreach ($cancelLeaveDates as $cancelkey => $cancelData) {

                $cancelData = (array) $cancelData;
                //check whether leave request detail record exsisit for cancelling date
                $detailRecord = DB::table('leaveRequestDetail')
                    ->where('leaveRequestDetail.leaveDate', $cancelData['cancelLeaveDate'])
                    ->where('leaveRequestDetail.leaveRequestId', $existingLeave['id'])
                    ->where('leaveRequestDetail.leavePeriodType', $cancelData['cancelLeavePeriodType'])
                    ->whereIn('status', ['PENDING', 'APPROVED'])->first();
                if (!is_null($detailRecord)) {

                    //update leave date status to cancelled
                    $updateRecord = DB::table('leaveRequestDetail')->where('leaveRequestDetail.id', $detailRecord->id)->update(['status' => 'CANCELED']);
                    $cancelLeaveDetailsIds[] = $detailRecord->id;
                    // update related leave request entitilment 
                    $updateLeaveRequestEntitlementRecord = DB::table('leaveRequestEntitlement')->where('leaveRequestEntitlement.leaveRequestDetailId', $detailRecord->id)->update(['entitlePortion' => 0]);
                } else {

                    $relatedDetailRecord = DB::table('leaveRequestDetail')
                        ->where('leaveRequestDetail.leaveDate', $cancelData['cancelLeaveDate'])
                        ->where('leaveRequestDetail.leaveRequestId', $existingLeave['id'])
                        ->where('leaveRequestDetail.leavePeriodType', $cancelData['orginalLeavePeriodType'])
                        ->whereIn('leaveRequestDetail.status', ['PENDING', 'APPROVED'])->first();

                    if (!is_null($relatedDetailRecord)) {
                        //update leave date status to cancelled
                        $updateRecord = DB::table('leaveRequestDetail')->where('leaveRequestDetail.id', $relatedDetailRecord->id)->update(['status' => 'CANCELED']);
                        $cancelLeaveDetailsIds[] = $relatedDetailRecord->id;
                        // update related leave request entitilment 
                        $updateLeaveRequestEntitlementRecord = DB::table('leaveRequestEntitlement')->where('leaveRequestEntitlement.leaveRequestDetailId', $relatedDetailRecord->id)->update(['entitlePortion' => 0]);

                        //create new detail record 
                        $leaveDetailData = [
                            'leaveRequestId' => $existingLeave['id'],
                            'leaveDate' => $cancelData['cancelLeaveDate'],
                            'status' => ($isFullyApprovedLeave) ? 'APPROVED' : 'PENDING',
                            'leavePeriodType' => ($cancelData['cancelLeavePeriodType'] == 'SECOND_HALF_DAY') ? 'FIRST_HALF_DAY' : 'SECOND_HALF_DAY'
                        ];

                        $leaveDetailSave = $this->store->insert($this->leaveRequestDetailModel, $leaveDetailData, true);
                    }
                }
            }

            //remove old leave request detail related entitlement records
            $remainDetailRecordsIds = array_values(array_diff($originalLeaveDatesIds, $cancelLeaveDetailsIds));
            $deleteLeaveRequestEntitlements =  DB::table('leaveRequestEntitlement')->whereIn('leaveRequestEntitlement.leaveRequestDetailId', $remainDetailRecordsIds)->delete();

            //add new leave dates for perticular leave
            $updateleaveDates = $this->addNewAllocationForUpdatedLeave($existingLeave, $newlyUpdatedLeaveDates, $isFullyApprovedLeave);

            if (!empty($existingLeave['workflowInstanceId'])) {
                $updatedLeave =  (array) $this->store->getById($this->leaveRequestModel, $id);
                $workflowDetail = json_encode($updatedLeave);

                //update new leave details to workflow details
                $updatedWorkflowDetail = DB::table('workflowDetail')->where('workflowDetail.instanceId', $existingLeave['workflowInstanceId'])->update(['details' => $workflowDetail]);
            }
        }

        //update cancel leave request state and cancel leave request detail records status
        $cancelLeaveRequestUpdated['currentState'] = 2;
        $cancelLeaveRequestUpdated['approvedBy'] = $relateUser->id;
        $cancelLeaveRequestUpdated['approvedAt'] = Carbon::now()->toDateTimeString();
        $updatePriorState = $this->store->updateById($this->cancelLeaveRequestModel, $cancelLeaveRequestId, $cancelLeaveRequestUpdated);

        $cancelLeaveRequestDetailUpdated['status'] = 'APPROVED';
        $updatePriorState = DB::table('cancelLeaveRequestDetail')->whereIn('cancelLeaveRequestDetail.id', $leaveCancelRequestDetailIds)->update(['status' => 'APPROVED']);

        $dataSet = [
            'employeeId' => $employeeId,
            'dates' => $leaveDates
        ];

        event(new AttendanceDateDetailChangedEvent($dataSet));
    }

    /**
     * Following function update leave allocation figures when change the leave dates by leave cancellation
     */
    private function addNewAllocationForUpdatedLeave($leaveRequestData, $dateDetails, $isFullyApprovedLeave)
    {
        $employeeCalendar = $this->workCalendarService->getEmployeeCalendar($leaveRequestData['employeeId']);

        $employeeId = $leaveRequestData['employeeId'];
        $leaveType = $this->leaveTypeService->getLeaveType($leaveRequestData['leaveTypeId'])['data'];
        $leaveType->workingDayIds = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
            ->where('leaveTypeId', $leaveRequestData['leaveTypeId'])
            ->get();


        $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
            ->where('leaveRequestId', $leaveRequestData['id'])
            ->whereIn('status', ['PENDING', 'APPROVED'])
            ->orderBy('leaveDate', 'asc')
            ->get()->toArray();

        $leaveRequestEntitlements = [];
        $newFromDate = null;
        $newToDate = null;

        foreach ($leaveRequestDetails as $key => $leaveDetail) {
            $leaveDetail = (array) $leaveDetail;
            $value = new DateTime($leaveDetail['leaveDate']);
            $datVal = $leaveDetail['leaveDate'];
            $entitlePortion = 0;

            //set new from date
            if ($key == 0) {
                $newFromDate = $datVal;
            }

            //set new to Date
            if ((sizeof($leaveRequestDetails) - 1) == $key) {
                $newToDate = $datVal;
            }

            $shift = $this->getShiftIfWorkingDay($leaveType, $leaveRequestData['employeeId'], $employeeCalendar, $value);

            if ( // if full day leaves
                $shift->noOfDay > 0
                && $dateDetails[$datVal] == 'FULL_DAY'
            ) {
                $entitlePortion = $shift->noOfDay;
            } else if ( // if half day leaves
                $shift->noOfDay >= 0.5
                && ($dateDetails[$datVal] == 'FIRST_HALF_DAY' || $dateDetails[$datVal] == 'SECOND_HALF_DAY')
            ) {
                $entitlePortion = 0.5;
            }

            $leaveRequestEntitlement = $this->leaveEntitlementAllocation(
                $employeeId,
                $leaveType,
                $value,
                $entitlePortion,
                $leaveRequestEntitlements
            );

            if (empty($leaveRequestEntitlement)) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_NOT_ENOUGH_ENTITLEMENT'), null);
            }

            $leaveRequestEntitlements = array_merge($leaveRequestEntitlements, $leaveRequestEntitlement);
        }

        foreach ($leaveRequestDetails as $detailkey => $leaveData) {
            $leaveData = (array) $leaveData;
            $leaveDates[] = $leaveData['leaveDate'];
            $date = $leaveData['leaveDate'];


            $dateRelatedEntitlements = array_filter($leaveRequestEntitlements, function ($entitlement) use ($date) {
                return $entitlement['date']->format('Y-m-d') == $date;
            });

            foreach ($dateRelatedEntitlements as $dateRelatedEntitlement) {
                unset($dateRelatedEntitlement['date']);
                $dateRelatedEntitlement['leaveRequestDetailId'] = $leaveData['id'];

                $leaveRequestEntitlementResponse = $this->store->insert($this->leaveRequestEntitlementModel, $dateRelatedEntitlement, true);
            }
        }

        if ($isFullyApprovedLeave) {

            $usedCountUpdates = [];
            foreach ($leaveRequestEntitlements as $entitlement) {
                if (empty($usedCountUpdates[$entitlement['leaveEntitlementId']])) {
                    $usedCountUpdates[$entitlement['leaveEntitlementId']] = $entitlement['entitlePortion'];
                } else {
                    $usedCountUpdates[$entitlement['leaveEntitlementId']] += $entitlement['entitlePortion'];
                }
            }

            $numLeaveDates = 0;
            foreach ($usedCountUpdates as $id => $usedCountUpdate) {
                $numLeaveDates += $usedCountUpdate;
                $usedCountUpdateResponse = $this->store->getFacade()::table('leaveEntitlement')
                    ->where('id', $id)
                    ->increment('usedCount', $usedCountUpdate);
            }
        } else {

            $pendingCountUpdates = [];
            foreach ($leaveRequestEntitlements as $entitlement) {
                if (empty($pendingCountUpdates[$entitlement['leaveEntitlementId']])) {
                    $pendingCountUpdates[$entitlement['leaveEntitlementId']] = $entitlement['entitlePortion'];
                } else {
                    $pendingCountUpdates[$entitlement['leaveEntitlementId']] += $entitlement['entitlePortion'];
                }
            }

            $numLeaveDates = 0;
            foreach ($pendingCountUpdates as $id => $pendingCountUpdate) {
                $numLeaveDates += $pendingCountUpdate;
                $pendingCountUpdateResponse = $this->store->getFacade()::table('leaveEntitlement')
                    ->where('id', $id)
                    ->increment('pendingCount', $pendingCountUpdate);
            }
        }

        //update num of leave dates
        $leaveRequstUpdatedData['numberOfLeaveDates'] = $numLeaveDates;
        $leaveRequstUpdatedData['fromDate'] = $newFromDate;
        $leaveRequstUpdatedData['toDate'] = $newToDate;
        $updateLeaveRequest = $this->store->updateById($this->leaveRequestModel, $leaveRequestData['id'], $leaveRequstUpdatedData);
    }
}
