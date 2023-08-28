<?php


namespace App\Services;

use Log;
use App\Exceptions\Exception;
use App\Services\EmployeeService;
use App\Library\ActiveDirectory;
use App\Library\AzureUser;
use App\Library\ModelValidator;
use App\Library\Session;
use App\Library\Redis;
use App\Library\Store;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;
use App\Jobs\EmailNotificationJob;
use App\Library\Email;
use App\Library\FileStore;
use App\Traits\SessionHelper;
use App\Traits\AttendanceProcess;
use App\Traits\EmployeeHelper;
use App\Events\AttendanceDateDetailChangedEvent;
use Carbon\Carbon;
use stdClass;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Arr;

/**
 * Name: WorkflowService
 * Purpose: Performs tasks related to the Workflow model.
 * Description: Workflow Service class is called by the WorkflowController where the requests related
 * to Workflow Model (basic operations and others). Table that is being modified is workflow.
 * Module Creator: Tharindu 
 */
class WorkflowService extends BaseService
{
    use JsonModelReader;
    use SessionHelper;
    use AttendanceProcess;
    use EmployeeHelper;

    private $store;
    private $workflowModel;
    private $userModel;
    private $userRoleModel;
    private $departmentModel;
    private $locationModel;
    private $workflowDefineModel;
    private $workflowInstanceModel;
    private $workflowApprovalLevelModel;
    private $employeeModel;
    private $session;
    private $employeeService;
    private $leaveRequestDetailModel;
    private $fileStorage;
    private $redis;
    private $modelService;
    private $emailNotificationService;
    private $workflowInstanceDetail;
    private $autoGenerateIdService;
    private $activeDirectory;
    private $azureUser;
    private $leaveRequestModel;
    private $resignationTypeModel;


    public function __construct(Store $store, Session $session, FileStore $fileStorage, Redis $redis, ModelService $modelService, EmailNotificationService $emailNotificationService, AutoGenerateIdService $autoGenerateIdService, ActiveDirectory $activeDirectory, AzureUser $azureUser)
    {
        $this->store = $store;
        $this->workflowModel = $this->getModel('workflowDetail', true);
        $this->userModel = $this->getModel('user', true);
        $this->userRoleModel = $this->getModel('userRole', true);
        $this->departmentModel =  $this->getModel('department', true);
        $this->locationModel =  $this->getModel('location', true);
        $this->resignationTypeModel =  $this->getModel('resignationType', true);
        $this->workflowDefineModel = $this->getModel('workflowDefine', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->workflowApprovalLevelModel = $this->getModel('workflowApprovalLevel', true);
        $this->leaveRequestDetailModel= $this->getModel('leaveRequestDetail', true);
        $this->leaveRequestModel= $this->getModel('leaveRequest', true);
        $this->shortLeaveRequestModel= $this->getModel('shortLeaveRequest', true);
        $this->workflowInstanceDetail= $this->getModel('workflowInstanceDetail', true);
        $this->session = $session;
        $this->redis = $redis;
        $this->employeeModel = $this->getModel('employee', true);
        $this->fileStorage = $fileStorage;
        $this->modelService = $modelService;
        $this->emailNotificationService = $emailNotificationService;
        $this->autoGenrateIdService = $autoGenerateIdService;
        $this->activeDirectory = $activeDirectory;
        $this->azureUser = $azureUser;
    }

    public function createWorkflow($workflow)
    {
        DB::beginTransaction();

        try {
            $context = $workflow['context'];
            $id = $workflow['id'];
            unset($workflow['context']);
            $validationResponse = ModelValidator::validate($this->employeeModel, $workflow, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('validation issue'), $validationResponse);
            }
            // unset($workflow['employments']);
            //  unset($workflow['jobs']);
            // unset($workflow['salaries']);
            $workflowDetail['employeeId'] =   $id;
            // $newWorkflow = $this->store->insert($this->workflowModel, $workflow, true);
            try {
                $existingEmployee = $this->store->getById($this->employeeModel, $id);
                if (empty($existingEmployee)) {
                    return $this->error(404, Lang::get('workflowMessages.basic.ERR_NOT_EXIST'), null);
                }

                $workFlowId = DB::table('workflowDefine')
                    ->select('workflowDefine.id', 'workflowDefine.contextId')
                    ->leftJoin('workflowContext', 'workflowDefine.contextId', '=', 'workflowContext.id')
                    ->where('workflowContext.contextName', '=', $context)
                    ->where('workflowDefine.isDelete', '=', false)
                    ->first();

                $queryBuilder = DB::table('workflowStateTransitions')
                    ->select('workflowStateTransitions.id', 'workflowStateTransitions.workflowId', 'workflowStateTransitions.actionId', 'workflowStateTransitions.priorStateId', 'workflowStateTransitions.postStateId')
                    ->whereNotIn(
                        'workflowStateTransitions.priorStateId',
                        function ($query) {
                            $query->select('workflowStateTransitions.postStateId')
                                ->from('workflowStateTransitions')
                                ->where('workflowStateTransitions.isDelete', '=', false)
                                ->get();
                        }
                    )
                    ->where([['workflowStateTransitions.workflowId', '=', $workFlowId->id], ['workflowStateTransitions.isDelete', '=', false]]);

                $insance = $queryBuilder->get();
                $actionIds = [];
                foreach ($insance as $object) {
                    $actionIds[] = $object->actionId;
                }
                $instanceData['workflowId'] = $workFlowId->id;
                $instanceData['actionId'] = json_encode($actionIds, true);
                $instanceData['priorState'] = $insance[0]->priorStateId;
                $instanceData['contextId'] = $workFlowId->contextId;

                $result = (array) $this->store->insert($this->workflowInstanceModel, $instanceData, true);

                $workflowDetail['instanceId'] = $result['id'];

                $workflowDetail['details'] = json_encode($workflow);

                $result = $this->store->insert($this->workflowModel, $workflowDetail, true);
                if (!$result) {
                    return $this->error(500, Lang::get('workflowMessages.basic.ERR_UPDATE'), $id);
                }
                DB::commit();
                return $this->success(200, Lang::get('workflowMessages.basic.SUCC_UPDATE'), $workflow);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                return $this->error(400, Lang::get('workflowMessages.basic.ERR_UPDATE'), null);
            }

            // return $this->success(201, Lang::get('workflowMessages.basic.SUCC_CREATE'), $newWorkflow);



        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_CREATE'), null);
        }
    }

    public function getAllWorkflow($id, $permittedFields, $queryParams)
    {
        try {
            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $roleDetails = [
                'adminRoleId' => !empty($this->session->getUser()->adminRoleId)?  $this->session->getUser()->adminRoleId : null,
                'managerRoleId' => !empty($this->session->getUser()->managerRoleId)?  $this->session->getUser()->managerRoleId : null
            ];
            $requestedUser =  DB::table('user')->where('employeeId', $requestedEmployeeId)->where('isDelete', false)->first();
            $managerPermission = $this->getManagerRoleWorkflowPermissions($this->session, $this->redis);
            $employeeIds = $managerPermission['employeeIds'];
            $managerEmployeeIds = $employeeIds;
            $contextIds = $managerPermission['workflows'];
            $mangerContextIds = $contextIds;
            $managerWorkflows = [];

            $adminPermission = $this->getAdminRoleWorkflowPermissions($this->session, $this->redis);
            $employeeIds = $adminPermission['employeeIds'];
            $adminEmployeeIds = $employeeIds;
            $contextIds = $adminPermission['workflows'];
            $adminContextIds = $contextIds;
            $adminWorkflows = [];

            $allEmployeeIds = array_values(array_unique(array_merge($managerEmployeeIds, $adminEmployeeIds)));
            $allContextIds = array_values(array_unique(array_merge($mangerContextIds, $adminContextIds)));
            $workflowRequestCount = 0;

            // get not reported employee workflows that current login user has direct access to perform
            $otherRelatedWorkflows = $this->getWorkFlowDataByDirectUserPermission($allEmployeeIds, $allContextIds, $queryParams, $requestedEmployeeId, $roleDetails);
            if (!empty($otherRelatedWorkflows['dataRecords']) && sizeof($otherRelatedWorkflows['dataRecords']) > 0) {
                $managerWorkflows = $otherRelatedWorkflows['dataRecords']->unique('id')->sortByDesc('createdAt')->values();
            }

            $workflowRequestCount = (!empty($otherRelatedWorkflows)) ?  $otherRelatedWorkflows['recordCount'] : 0;
           
            if (!is_null($queryParams['contextType'])) {
                if ($queryParams['contextType'] == 'all' && (empty($mangerContextIds) && empty($adminContextIds))) {
                    return $this->error('403', 'permission deniend', null);    
                }

                if (!empty($mangerContextIds) && !empty($adminContextIds)) {
                    if ($queryParams['contextType'] != 'all' && $queryParams['contextType'] != 'all' && ((!in_array($queryParams['contextType'], $mangerContextIds) && $mangerContextIds[0] != '*') && (!in_array($queryParams['contextType'], $adminContextIds) && $adminContextIds[0] != '*'))) {
                        return $this->error('403', 'permission deniend', null);  
                    }
                } elseif (empty($mangerContextIds) && !empty($adminContextIds)) {
                    if ($queryParams['contextType'] != 'all' && (!in_array($queryParams['contextType'], $adminContextIds) &&  $adminContextIds[0] != '*')) {
                        return $this->error('403', 'permission deniend', null);  
                    }
                } elseif (!empty($mangerContextIds) && empty($adminContextIds)) {
                    if ($queryParams['contextType'] != 'all' && ((!in_array($queryParams['contextType'], $mangerContextIds) && $mangerContextIds[0] != '*') )) {
                        return $this->error('403', 'permission deniend', null);  
                    }
                }

                if (!empty($managerWorkflows) && !empty($adminWorkflows)) {
                    $result = $managerWorkflows->merge($adminWorkflows)->unique('id')->sortByDesc('createdAt')->values()->all();
                } elseif (!empty($managerWorkflows) && empty($adminWorkflows)) {
                    $result = $managerWorkflows;
                } elseif (!empty($adminWorkflows) && empty($managerWorkflows)) {
                    $result = $adminWorkflows;
                } else {
                    $result = [];
                }
            } else {
                $result = $managerWorkflows->merge($adminWorkflows)->unique('id')->sortByDesc('createdAt')->values()->all();
            }

            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;

            foreach ($result as $key => $value) {
                
                $value = (array) $value;
                $workflowId = $value['workflowId'];

                //get related workflow detail
                $workflowDetailRecord = DB::table('workflowDetail')->select(['id', 'details'])->where('isDelete', false)->where('instanceId', $value['id'])->first();
                $details = (!is_null($workflowDetailRecord)) ? (array) json_decode($workflowDetailRecord->details) : [];
                
                if ($value['currentStateId'] != 2) {
                    $result[$key]->isInFinalSucessState = false;
                } else {
                    $result[$key]->isInFinalSucessState = true;
                }

                if ($value['currentStateId'] != 3 && $value['currentStateId'] != 4) {
                    $result[$key]->isInFinalFaliureState = false;
                } else {
                    $result[$key]->isInFinalFaliureState = true;
                }

                
                if ($value['currentStateId'] == 1) {
                    $result[$key]->priorStateName = 'Pending'; 
                    $result[$key]->stateTagColor = 'orange';  
                } elseif ($value['currentStateId'] == 2) {
                    $result[$key]->priorStateName = 'Approved';   
                    $result[$key]->stateTagColor = 'green';
                } elseif ($value['currentStateId'] == 3) {
                    $result[$key]->priorStateName = 'Rejected';
                    $result[$key]->stateTagColor = 'red';
                } elseif ($value['currentStateId'] == 4) {
                    $result[$key]->priorStateName = 'Cancelled';
                    $result[$key]->stateTagColor = 'geekblue';
                }

                if ($value['contextId'] == 2) {
                    $leaveRequestModel = $this->getModel('leaveRequest', true);
                    $leaveData = (array)$this->store->getById($leaveRequestModel, $details['id']);
                    if (empty($leaveData)) {
                        return $this->error(404, Lang::get('workflowMessages.basic.ERR_NOT_EXIST'), null);
                    }

                    $initialState =  1;
                    $comments = json_decode($leaveData['comments']);
                    $result[$key]->commentCount = sizeof($comments);
                    $result[$key]->leaveRequestId = $details['id'];
                    $details['currentState'] = $leaveData['currentState'];

                    if ($leaveData['currentState'] != $initialState) {
                        $details['isInInitialState'] = false;
                    } else {

                        if ($leaveData['workflowInstanceId']) {
                            //get instance related levels ids
                            $instanceApproverLevelIdArray= DB::table('workflowInstanceApprovalLevel')
                            ->where('workflowInstanceApprovalLevel.workflowInstanceId', '=', $leaveData['workflowInstanceId'])
                            ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)
                            ->pluck('workflowInstanceApprovalLevel.id')->toArray();
    
                            if (sizeof($instanceApproverLevelIdArray) > 0)  {
                                //check whether has any actions peroform on that 
                                $instanceApproverLevelDetailCount= DB::table('workflowInstanceApprovalLevelDetail')
                                ->whereIn('workflowInstanceApprovalLevelDetail.workflowInstanceApproverLevelId', $instanceApproverLevelIdArray)->count();
    
                                $details['isInInitialState'] = ($instanceApproverLevelDetailCount > 0) ? false : true;
                            }
                        }

                    }



                    $details['isInInitialState'] = ($leaveData['currentState'] === $initialState) ? true : false;
                    $details['canShowApprovalLevel'] = true;

                    //TO DO
                    // if ($leaveData['currentState'] == 4 && !empty($leaveData['workflowInstanceId'])) {
                    //    $instanceDetail =  DB::table('workflowInstanceDetail')->where('instanceId', $leaveData['workflowInstanceId'])->where('postState', 4)->first();

                    //    if (!is_null($instanceDetail)) {
                    //         $details['canShowApprovalLevel'] = ($instanceDetail->priorState === $initialState) ? false : true;
                    //    }
                    // }

                    //check whether leave has pending leave cancel leave requests
                    $hasPendingCancelLeaveRequests = $this->checkHasPendingCancelLeaveRequests($details);

                    $details['canCancelLeaveRequest'] = (!$hasPendingCancelLeaveRequests && $requestedUser->id === $value['createdBy'] && !$result[$key]->isInFinalFaliureState) ? true : false;
                    $details['workflowInstanceId'] = (!empty($leaveData['workflowInstanceId'])) ? $leaveData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 3) {
                    $timeChangeRequestModel = $this->getModel('attendanceTimeChange', true);
                    $timeChangeData = (array)$this->store->getById($timeChangeRequestModel, $details['id']);
                    if (empty($timeChangeData)) {
                        return $this->error(404, Lang::get('workflowMessages.basic.ERR_NOT_EXIST'), null);
                    }
                    $details['currentState'] = $value['currentStateId'];
                    $details['workflowInstanceId'] = (!empty($timeChangeData['workflowInstanceId'])) ? $timeChangeData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 4) {
                    $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                    $shortLeaveData = (array)$this->store->getById($shortLeaveRequestModel, $details['id']);
                    if (empty($shortLeaveData)) {
                        return $this->error(404, Lang::get('workflowMessages.basic.ERR_NOT_EXIST'), null);
                    }

                    //check whether leave has pending leave cancel leave requests
                    $hasPendingCancelShortLeaveRequests = $this->checkHasPendingCancelShortLeaveRequests($details);

                    $details['canCancelShortLeaveRequest'] = (!$hasPendingCancelShortLeaveRequests && $requestedUser->id === $value['createdBy'] && !$result[$key]->isInFinalFaliureState) ? true : false;

                    $initialState =  1;
                    $details['isInInitialState'] = true;
                    if ($shortLeaveData['currentState'] != $initialState) {
                        $details['isInInitialState'] = false;
                    } else {

                        if ($shortLeaveData['workflowInstanceId']) {
                            //get instance related levels ids
                            $instanceApproverLevelIdArray= DB::table('workflowInstanceApprovalLevel')
                            ->where('workflowInstanceApprovalLevel.workflowInstanceId', '=', $shortLeaveData['workflowInstanceId'])
                            ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)
                            ->pluck('workflowInstanceApprovalLevel.id')->toArray();

                            if (sizeof($instanceApproverLevelIdArray) > 0)  {
                                //check whether has any actions peroform on that 
                                $instanceApproverLevelDetailCount= DB::table('workflowInstanceApprovalLevelDetail')
                                ->whereIn('workflowInstanceApprovalLevelDetail.workflowInstanceApproverLevelId', $instanceApproverLevelIdArray)->count();

                                $details['isInInitialState'] = ($instanceApproverLevelDetailCount > 0) ? false : true;
                            }
                        }

                    }

                    $details['canShowApprovalLevel'] = true;

                    // TO DO

                    if ($shortLeaveData['currentState'] == 4 && !empty($shortLeaveData['workflowInstanceId'])) {
                        $instanceApproveLevel =  DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', $shortLeaveData['workflowInstanceId'])->where('levelStatus', 'CANCELED')->where('levelSequence','!=',0)->first();

                        $details['canShowApprovalLevel'] = !is_null($instanceApproveLevel) ? true : false;
                    }


                    $details['currentState'] = $shortLeaveData['currentState'];
                    $details['workflowInstanceId'] = (!empty($shortLeaveData['workflowInstanceId'])) ? $shortLeaveData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 5) {
                    $workshiftModel =  $this->getModel('workShifts', true);
                    $newShiftData = (array) $this->store->getById($workshiftModel, $details['newShiftId']);
                    $currentShiftData = (array) $this->store->getById($workshiftModel, $details['currentShiftId']);

                    $shiftChangeRequestModel =  $this->getModel('shiftChangeRequest', true);
                    $shiftRequestData = (array) $this->store->getById($shiftChangeRequestModel, $details['id']);

                    $details['currentShiftName'] = (!empty($currentShiftData)) ? $currentShiftData['name'] : '-';
                    $details['newShiftName'] = $newShiftData['name'];
                    $details['workflowInstanceId'] = (!empty($shiftRequestData['workflowInstanceId'])) ? $shiftRequestData['workflowInstanceId'] : null;
                    $details['currentState'] = $shiftRequestData['currentState'];

                }

                if ($value['contextId'] == 6) {
                    $cancelLeaveRequestModel =  $this->getModel('cancelLeaveRequest', true);
                    $cancelLeaveRequestData = (array) $this->store->getById($cancelLeaveRequestModel, $details['id']);

                    $leaveRequestModel = $this->getModel('leaveRequest', true);
                    $leaveData = (array)$this->store->getById($leaveRequestModel, $details['leaveRequestId']);

                    $details['currentState'] = $cancelLeaveRequestData['currentState'];
                    $details['fromDate'] = $leaveData['fromDate'];
                    $details['toDate'] = $leaveData['toDate'];
                    $details['leaveTypeId'] = $leaveData['leaveTypeId'];
                    $details['workflowInstanceId'] = (!empty($cancelLeaveRequestData['workflowInstanceId'])) ? $cancelLeaveRequestData['workflowInstanceId'] : null;
                }
                if ($value['contextId'] == 7) {
                    $resignationTypeData = (array)$this->store->getById($this->resignationTypeModel, $details['resignationTypeId']);
                    $details['resignationType'] = (!is_null($resignationTypeData) && !empty($resignationTypeData)) ?  $resignationTypeData['name'] : '-';
                }

                if ($value['contextId'] == 8) {
                    $cancelShortLeaveRequestModel =  $this->getModel('cancelShortLeaveRequest', true);
                    $cancelShortLeaveRequestData = (array) $this->store->getById($cancelShortLeaveRequestModel, $details['id']);

                    $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                    $leaveData = (array)$this->store->getById($shortLeaveRequestModel, $details['shortLeaveRequestId']);


                    $details['currentState'] = $cancelShortLeaveRequestData['currentState'];
                    $details['workflowInstanceId'] = (!empty($cancelShortLeaveRequestData['workflowInstanceId'])) ? $cancelShortLeaveRequestData['workflowInstanceId'] : null;
                }

                if ($value['contextId'] == 9) {
                    $claimRequestModel =  $this->getModel('claimRequest', true);
                    $claimRequestData = (array) $this->store->getById($claimRequestModel, $details['id']);

                    
                    $details['currentState'] = $claimRequestData['currentState'];
                    $details['workflowInstanceId'] = (!empty($claimRequestData['workflowInstanceId'])) ? $claimRequestData['workflowInstanceId'] : null;
                }

                if ($value['contextId'] == 10) {
                    $postOtRequestModel =  $this->getModel('postOtRequest', true);
                    $postOtRequestData = (array) $this->store->getById($postOtRequestModel, $details['id']);

                    $details['currentState'] = $postOtRequestData['currentState'];
                    $details['workflowInstanceId'] = (!empty($postOtRequestData['workflowInstanceId'])) ? $postOtRequestData['workflowInstanceId'] : null;
                }

                $result[$key]->details = json_encode($details);

                // set display heading 1
                $result[$key]->displayHeading1 = $this->generateHeading1($value['contextId'], $details);
                $result[$key]->displayHeading2 = $this->generateHeading2($value['contextId'], $details);

                $createdAt = isset($details['createdAt']) && !empty($details['createdAt']) ? $details['createdAt'] : $value['createdAt'];
                $result[$key]->updatedAt =  $this->getFormattedDateForList($value['updatedAt'], $timeZone);
                $result[$key]->requestedOn = $this->getFormattedDateForList($createdAt, $timeZone);
            }

            $responce = new stdClass();
            $responce->count = $workflowRequestCount;
            $responce->sheets = $result;
            $responce->success = true;

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_ALL_RETRIVE'), $responce);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    public function getPendingWorkflowCountsForTodoWidget($id, $permittedFields, $queryParams)
    {
        try {
            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $roleDetails = [
                'adminRoleId' => !empty($this->session->getUser()->adminRoleId)?  $this->session->getUser()->adminRoleId : null,
                'managerRoleId' => !empty($this->session->getUser()->managerRoleId)?  $this->session->getUser()->managerRoleId : null
            ];
            $requestedUser =  DB::table('user')->where('employeeId', $requestedEmployeeId)->where('isDelete', false)->first();
            $managerPermission = $this->getManagerRoleWorkflowPermissions($this->session, $this->redis);
            $employeeIds = $managerPermission['employeeIds'];
            $managerEmployeeIds = $employeeIds;
            $contextIds = $managerPermission['workflows'];
            $mangerContextIds = $contextIds;
            $managerWorkflows = [];

            $adminPermission = $this->getAdminRoleWorkflowPermissions($this->session, $this->redis);
            $employeeIds = $adminPermission['employeeIds'];
            $adminEmployeeIds = $employeeIds;
            $contextIds = $adminPermission['workflows'];
            $adminContextIds = $contextIds;
            $adminWorkflows = [];

            $allEmployeeIds = array_values(array_unique(array_merge($managerEmployeeIds, $adminEmployeeIds)));
            $allContextIds = array_values(array_unique(array_merge($mangerContextIds, $adminContextIds)));

            // get not reported employee workflows that current login user has direct access to perform
            $otherRelatedWorkflowsCount = $this->getWorkFlowCountByDirectUserPermission($allEmployeeIds, $allContextIds, $queryParams, $requestedEmployeeId, $roleDetails);
            return $otherRelatedWorkflowsCount;

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    private function checkHasPendingCancelLeaveRequests($leaveRequstData)
    {
        $leaveRequstData = (array) $leaveRequstData;
        //get all cancel leave request that go through workflow that realte to leave request id
        $cancelRequestData = DB::table('cancelLeaveRequest')
            ->where('cancelLeaveRequest.leaveRequestId', '=', $leaveRequstData['id'])
            ->whereNotNull('workflowInstanceId')
            ->get();
        
        if(!is_null($cancelRequestData) && sizeof($cancelRequestData) > 0) {
            $hasPending = false;
            foreach ($cancelRequestData as $key => $value) {
                $value = (array) $value;
                $cancelRequestData = DB::table('workflowInstance')
                ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                ->where('workflowInstance.id', '=', $value['workflowInstanceId'])
                ->where('workflowInstance.currentStateId', 1) // id of the pending state is 1
                ->get();

                if (sizeof($cancelRequestData) > 0) {
                    $hasPending = true;
                    break;
                }

            }

            return $hasPending;
        } else {
            return false;
        }
    }

    private function checkHasPendingCancelShortLeaveRequests($shortLeaveRequstData)
    {

        $shortLeaveRequstData = (array) $shortLeaveRequstData;
        //get all cancel leave request that go through workflow that realte to leave request id
        $cancelRequestData = DB::table('cancelShortLeaveRequest')
            ->where('cancelShortLeaveRequest.shortLeaveRequestId', '=', $shortLeaveRequstData['id'])
            ->whereNotNull('workflowInstanceId')
            ->get();
        
        if(!is_null($cancelRequestData) && sizeof($cancelRequestData) > 0) {
            $hasPending = false;
            foreach ($cancelRequestData as $key => $value) {
                $value = (array) $value;
                $cancelRequestData = DB::table('workflowInstance')
                ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                ->where('workflowInstance.id', '=', $value['workflowInstanceId'])
                ->where('workflowInstance.currentStateId', 1) // id of the pending state is 1
                ->get();

                if (sizeof($cancelRequestData) > 0) {
                    $hasPending = true;
                    break;
                }

            }

            return $hasPending;
        } else {
            return false;
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

    private function getWorkFlowDataByRolePermissions($employeeIds, $contextIds, $queryParams)
    {
        $queryBuilder = DB::table('workflowInstance')
                            ->select(
                                'workflowInstance.*',
                                'workflowState.stateName as priorStateName',
                                'workflowDetail.employeeId',
                                'workflowDetail.details',
                                'workflowContext.contextName',
                                'workflowDefine.contextId',
                                'employee.firstName',
                                'employee.lastName',
                                'location.name as locationName',
                                'department.name as departmentName',
                                'division.name as divisionName',
                                'jobTitle.name as jobTitleName',
                                "workflowInstance.currentStateId"
                            )
                            ->leftJoin('workflowState', 'workflowInstance.currentStateId', '=', 'workflowState.id')
                            ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                            ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
                            ->leftJoin('workflowContext', 'workflowContext.id', '=', 'workflowDefine.contextId')
                            ->leftJoin('employee', 'employee.id', '=', 'workflowDetail.employeeId')
                            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                            ->leftJoin('location', 'location.id', '=', 'employeeJob.locationId')
                            ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
                            ->leftJoin('division', 'division.id', '=', 'employeeJob.divisionId')
                            ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
                            ->where('workflowInstance.isDelete', '=', false)
                            ->orderBy('workflowInstance.createdAt', 'DESC')
                            ->whereIn('workflowDetail.employeeId', $employeeIds);

        if (!is_null($queryParams['contextType'])) {
            if (!in_array('*', $contextIds) && $queryParams['contextType'] != 'all') { 
                if (in_array($queryParams['contextType'], $contextIds)) {
                    $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
                } else {
                    return [];
                }
            } elseif (!in_array('*', $contextIds) && $queryParams['contextType'] == 'all') {
                $queryBuilder = $queryBuilder->whereIn('workflowDefine.contextId', $contextIds);
            }
    
            if (in_array('*', $contextIds) && $queryParams['contextType'] != 'all') { 
                $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
            }
    
        } else {
            if (!in_array('*', $contextIds)) {
                $queryBuilder = $queryBuilder->whereIn('workflowDefine.contextId', $contextIds);
            }
        }
        if ($queryParams['filters'] != "All") {
            // $queryBuilder =  $queryBuilder->where('workflowState.stateName', '=', $queryParams['filters']);
            switch ($queryParams['filters']) {
                case 'Pending':
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 1);
                    break;
                case 'Approved':
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 2);
                    break;
                case 'Rejected':
                    //assume user use default reject state that provide in workflow states
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 3);
                    break;
                case 'Cancelled':
                    //assume user use default reject state that provide in workflow states
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 4);
                    break;
                
                default:
                    $queryBuilder =  $queryBuilder->where('workflowState.stateName', '=', $queryParams['filters']);
                    break;
            }
        }
        return $queryBuilder->get();
    }


    private function getWorkFlowDataByDirectUserPermission($employeeIds, $contextIds, $queryParams, $loggedEmployeeId, $roleDetails)
    {

        $employeeData = DB::table('employee')
                ->select('employeeJob.jobTitleId', 'employeeJob.jobCategoryId')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')->where('employee.id', $loggedEmployeeId)->first();

        $hasReportingPersonPermission = true;
        $employeeJobCategoryId = null;
        $employeeJobTitleId = null;
        $managerRoleId = (!empty($roleDetails['managerRoleId'])) ? 3 : null;
        $adminRoleId = (!empty($roleDetails['adminRoleId'])) ? 1 : null;
        if (!empty($employeeData)) {
            $employeeJobCategoryId = (!empty($employeeData->jobCategoryId)) ? $employeeData->jobCategoryId : null;
            $employeeJobTitleId = (!empty($employeeData->jobTitleId)) ? $employeeData->jobTitleId : null;
            
        }

        $queryBuilder = DB::table('workflowInstance')
            ->select(
                'workflowInstance.*',
                'workflowState.stateName as priorStateName',
                'workflowInstance.workflowEmployeeId as employeeId',
                'workflowContext.contextName',
                'employee.firstName',
                'employee.lastName',
                'workflowDefine.contextId',
                'jobTitle.name as jobTitleName',
                "workflowInstance.currentStateId",
                'employeeJob.reportsToEmployeeId'
            )
            ->leftJoin('workflowState', 'workflowInstance.currentStateId', '=', 'workflowState.id')
            ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
            ->leftJoin('workflowContext', 'workflowContext.id', '=', 'workflowDefine.contextId')
            ->leftJoin('employee', 'employee.id', '=', 'workflowInstance.workflowEmployeeId')
            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
            ->where('workflowInstance.isDelete', '=', false)
            ->where(function($query) use ($loggedEmployeeId, $employeeJobCategoryId, $employeeJobTitleId, $managerRoleId, $adminRoleId)
                {
                    $query->whereJsonContains('workflowInstance.levelPermittedEmployees', (int)$loggedEmployeeId);
                    $query->orwhere(function($q) use ($loggedEmployeeId)
                    {
                        $q->whereJsonContains('workflowInstance.levelPermittedCommonOptions', "REPORTING_PERSON");
                        $q->whereNotNull('employeeJob.reportsToEmployeeId');
                        $q->where('employeeJob.reportsToEmployeeId', $loggedEmployeeId);
                    });

                    if (!is_null($employeeJobCategoryId)) {
                        $query->orwhereJsonContains('workflowInstance.levelPermittedJobCategories', (int)$employeeJobCategoryId);
                    }
            
                    if (!is_null($employeeJobTitleId)) {
                        $query->orwhereJsonContains('workflowInstance.levelPermittedDesignations', (int)$employeeJobTitleId);
                    }
            
                    if (!is_null($managerRoleId)) {
                        $query->orWhereJsonContains('workflowInstance.levelPermittedUserRoles', (int)$managerRoleId);
                    }
            
                    if (!is_null($adminRoleId)) {
                        $query->orWhereJsonContains('workflowInstance.levelPermittedUserRoles', (int)$adminRoleId);
                    }

                })
            ->orderBy('workflowInstance.createdAt', 'DESC');
            

        if (!is_null($queryParams['contextType'])) {
            if (!in_array('*', $contextIds) && $queryParams['contextType'] != 'all') { 
                if (in_array($queryParams['contextType'], $contextIds)) {
                    $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
                } else {
                    return [];
                }
            } elseif (!in_array('*', $contextIds) && $queryParams['contextType'] == 'all') {
                $queryBuilder = $queryBuilder->whereIn('workflowDefine.contextId', $contextIds);
            }
    
            if (in_array('*', $contextIds) && $queryParams['contextType'] != 'all') { 
                $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
            }
    
        } else {
            if (!in_array('*', $contextIds)) {
                $queryBuilder = $queryBuilder->whereIn('workflowDefine.contextId', $contextIds);
            }
        }

        if ($queryParams['filters'] != "All") {
            switch ($queryParams['filters']) {
                case 'Pending':
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 1);
                    break;
                case 'Approved':
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 2);
                    break;
                case 'Rejected':
                    //assume user use default reject state that provide in workflow states
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 3);
                    break;
                case 'Cancelled':
                    //assume user use default reject state that provide in workflow states
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 4);
                    break;
                
                default:
                    $queryBuilder =  $queryBuilder->where('workflowState.stateName', '=', $queryParams['filters']);
                    break;
            }
        }

        $recordCount = $queryBuilder->count();

        if ($queryParams['current'] && $queryParams['pageSize']) {
            $skip = ($queryParams['current'] - 1) * $queryParams['pageSize'];
            $queryBuilder = $queryBuilder->skip($skip)->take($queryParams['pageSize']);
        }

        $dataSet = [
            'recordCount' => $recordCount,
            'dataRecords' => $queryBuilder->get()
        ];

        return $dataSet;
    }


    private function getWorkFlowCountByDirectUserPermission($employeeIds, $contextIds, $queryParams, $loggedEmployeeId, $roleDetails)
    {

        $employeeData = DB::table('employee')
                ->select('employeeJob.jobTitleId', 'employeeJob.jobCategoryId')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')->where('employee.id', $loggedEmployeeId)->first();

        $hasReportingPersonPermission = true;
        $employeeJobCategoryId = null;
        $employeeJobTitleId = null;
        $managerRoleId = (!empty($roleDetails['managerRoleId'])) ? 3 : null;
        $adminRoleId = (!empty($roleDetails['adminRoleId'])) ? 1 : null;
        if (!empty($employeeData)) {
            $employeeJobCategoryId = (!empty($employeeData->jobCategoryId)) ? $employeeData->jobCategoryId : null;
            $employeeJobTitleId = (!empty($employeeData->jobTitleId)) ? $employeeData->jobTitleId : null;
            
        }

        $field = 'workflowDefine.contextId';
        $queryBuilder = DB::table('workflowInstance')
            ->select(
                'workflowDefine.contextId',
                DB::raw('count('.$field.') as total_count'),
            )
            ->leftJoin('workflowState', 'workflowInstance.currentStateId', '=', 'workflowState.id')
            ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
            // ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
            ->leftJoin('workflowContext', 'workflowContext.id', '=', 'workflowDefine.contextId')
            ->leftJoin('employee', 'employee.id', '=', 'workflowInstance.workflowEmployeeId')
            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->leftJoin('location', 'location.id', '=', 'employeeJob.locationId')
            ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
            ->leftJoin('division', 'division.id', '=', 'employeeJob.divisionId')
            ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
            ->where('workflowInstance.isDelete', '=', false)
            ->where(function($query) use ($loggedEmployeeId, $employeeJobCategoryId, $employeeJobTitleId, $managerRoleId, $adminRoleId)
                {
                    $query->whereJsonContains('workflowInstance.levelPermittedEmployees', (int)$loggedEmployeeId);
                    $query->orwhere(function($q) use ($loggedEmployeeId)
                    {
                        $q->whereJsonContains('workflowInstance.levelPermittedCommonOptions', "REPORTING_PERSON");
                        $q->whereNotNull('employeeJob.reportsToEmployeeId');
                        $q->where('employeeJob.reportsToEmployeeId', $loggedEmployeeId);
                    });

                    if (!is_null($employeeJobCategoryId)) {
                        $query->orwhereJsonContains('workflowInstance.levelPermittedJobCategories', (int)$employeeJobCategoryId);
                    }
            
                    if (!is_null($employeeJobTitleId)) {
                        $query->orwhereJsonContains('workflowInstance.levelPermittedDesignations', (int)$employeeJobTitleId);
                    }
            
                    if (!is_null($managerRoleId)) {
                        $query->orWhereJsonContains('workflowInstance.levelPermittedUserRoles', (int)$managerRoleId);
                    }
            
                    if (!is_null($adminRoleId)) {
                        $query->orWhereJsonContains('workflowInstance.levelPermittedUserRoles', (int)$adminRoleId);
                    }

                })
            ->orderBy('workflowInstance.createdAt', 'DESC');
            

        if (!is_null($queryParams['contextType'])) {
            if (!in_array('*', $contextIds) && $queryParams['contextType'] != 'all') { 
                if (in_array($queryParams['contextType'], $contextIds)) {
                    $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
                } else {
                    return [];
                }
            } elseif (!in_array('*', $contextIds) && $queryParams['contextType'] == 'all') {
                $queryBuilder = $queryBuilder->whereIn('workflowDefine.contextId', $contextIds);
            }
    
            if (in_array('*', $contextIds) && $queryParams['contextType'] != 'all') { 
                $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
            }
    
        } else {
            if (!in_array('*', $contextIds)) {
                $queryBuilder = $queryBuilder->whereIn('workflowDefine.contextId', $contextIds);
            }
        }

        if ($queryParams['filters'] != "All") {
            switch ($queryParams['filters']) {
                case 'Pending':
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 1);
                    break;
                case 'Approved':
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 2);
                    break;
                case 'Rejected':
                    //assume user use default reject state that provide in workflow states
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 3);
                    break;
                case 'Cancelled':
                    //assume user use default reject state that provide in workflow states
                    $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 4);
                    break;
                
                default:
                    $queryBuilder =  $queryBuilder->where('workflowState.stateName', '=', $queryParams['filters']);
                    break;
            }
        }

        return $queryBuilder->groupBy('workflowDefine.contextId')->get();
    }


    public function getWorkflow($queryParams)
    {
        try {
            $relatedEmployee = $this->session->user->employeeId;
            $relatedUser = $this->session->user->id;
            $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowInstance.workflowEmployeeId as employeeId',
                    'workflowDefine.contextId',
                    'workflowState.stateName as priorStateName',
                    "workflowContext.contextName",
                    'employee.firstName',
                    'employee.lastName',
                    "workflowInstance.currentStateId"
                )
                ->leftJoin('workflowState', 'workflowInstance.currentStateId', '=', 'workflowState.id')
                ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                ->leftJoin('employee', 'employee.id', '=', 'workflowInstance.workflowEmployeeId')
                ->leftJoin('workflowContext', 'workflowContext.id', '=', 'workflowDefine.contextId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.workflowEmployeeId', '=', $this->session->user->employeeId]])
                ->orderBy('workflowInstance.createdAt','DESC');

            if ($queryParams['contextType'] != 'all') { 
                $queryBuilder = $queryBuilder->where('workflowDefine.contextId', '=',$queryParams['contextType']);
            }
        
            if ($queryParams['filters'] != "All") {

                switch ($queryParams['filters']) {
                    case 'Pending':
                        $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 1);
                        break;
                    case 'Approved':
                        $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 2);
                        break;
                    case 'Rejected':
                        //assume user use default reject state that provide in workflow states
                        $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 3);
                        break;
                    case 'Cancelled':
                        //assume user use default reject state that provide in workflow states
                        $queryBuilder =  $queryBuilder->where('workflowInstance.currentStateId', 4);
                        break;
                    
                    default:
                        // $queryBuilder =  $queryBuilder->where('workflowState.stateName', '=', $queryParams['filters']);
                        break;
                }
            }
            $result = $queryBuilder->get();
            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;

            //get leave Requests that is manage covering person and covering person state is Pending, Declined or Canceled
            if ($queryParams['contextType'] == 'all' || $queryParams['contextType'] == 2) {
                $coveringPersonLeaveRequest = $this->getLeaveRquestsThatManageCoveringPerosns($this->session->user->employeeId, $queryParams);

                if (!empty($coveringPersonLeaveRequest) && sizeof($coveringPersonLeaveRequest) > 0) {
                    $result = $result->merge($coveringPersonLeaveRequest)->unique('id')->sortByDesc('createdAt')->values();
                }
            }


            $pageNo = $queryParams['current'];
            $offset = $queryParams['pageSize'];

            $totalRecordCount = $result->count();
            $totalNumOfPages = ceil($totalRecordCount / $offset);

            if (!is_null($pageNo) && !is_null($offset)) {
                $skip = ($pageNo - 1) * $offset;
                $currentPage = (int)$pageNo;
                $result = $result->skip($skip)->take($offset)->all();
            }

            $dataArray = [];

            foreach ($result as $key => $value) {
                $value = (array) $value;
                $workflowId = $value['workflowId'];

                if (!isset($value['details'])) {
                    //get related workflow detail
                    $workflowDetailRecord = DB::table('workflowDetail')->select(['id', 'details'])->where('isDelete', false)->where('instanceId', $value['id'])->first();
                    $details = (!is_null($workflowDetailRecord)) ? (object) json_decode($workflowDetailRecord->details) : (object) [];
                } else {
                    $details = json_decode($value['details']);
                }

                $workFlowData = DB::table('workflowDefine')
                ->select('workflowDefine.id', 'workflowDefine.contextId')
                ->where('workflowDefine.id', '=', $workflowId)
                ->where('workflowDefine.isDelete', '=', false)
                ->first();
                
                $workFlowData = (array) $workFlowData;

                if ($value['currentStateId'] != 2) {
                    $result[$key]->isInFinalSucessState = false;
                } else {
                    $result[$key]->isInFinalSucessState = true;
                }

                if ($value['currentStateId'] != 3 && $value['currentStateId'] != 4) {
                    $result[$key]->isInFinalFaliureState = false;
                } else {
                    $result[$key]->isInFinalFaliureState = true;
                }


                if ($value['currentStateId'] == 1) {
                    $result[$key]->priorStateName = 'Pending'; 
                    $result[$key]->stateTagColor = 'orange';  
                } elseif ($value['currentStateId'] == 2) {
                    $result[$key]->priorStateName = 'Approved';   
                    $result[$key]->stateTagColor = 'green';
                } elseif ($value['currentStateId'] == 3) {
                    $result[$key]->priorStateName = 'Rejected';
                    $result[$key]->stateTagColor = 'red';
                } elseif ($value['currentStateId'] == 4) {
                    $result[$key]->priorStateName = 'Cancelled';
                    $result[$key]->stateTagColor = 'geekblue';
                }

                if ($value['contextId'] == 2) {
                    $leaveRequestModel = $this->getModel('leaveRequest', true);
                    $leaveData = (array)$this->store->getById($leaveRequestModel, $details->id);
                    $initialState =  1;
                    if (!empty($leaveData)) {
                        $comments = json_decode($leaveData['comments']);
                        $result[$key]->commentCount = sizeof($comments);
                        $result[$key]->leaveRequestId = $details->id;  

                        $details->currentState = $leaveData['currentState'];
                        $details->isInInitialState = true;

                        if ($leaveData['currentState'] != $initialState) {
                            $details->isInInitialState = false;
                        } else {

                            if ($leaveData['workflowInstanceId']) {
                                //get instance related levels ids
                                $instanceApproverLevelIdArray= DB::table('workflowInstanceApprovalLevel')
                                ->where('workflowInstanceApprovalLevel.workflowInstanceId', '=', $leaveData['workflowInstanceId'])
                                ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)
                                ->pluck('workflowInstanceApprovalLevel.id')->toArray();
    
                                if (sizeof($instanceApproverLevelIdArray) > 0)  {
                                    //check whether has any actions peroform on that 
                                    $instanceApproverLevelDetailCount= DB::table('workflowInstanceApprovalLevelDetail')
                                    ->whereIn('workflowInstanceApprovalLevelDetail.workflowInstanceApproverLevelId', $instanceApproverLevelIdArray)->count();
    
                                    $details->isInInitialState = ($instanceApproverLevelDetailCount > 0) ? false : true;
                                }
                            }

                        }

                        $requestedUser =  DB::table('user')->where('employeeId', $relatedEmployee)->where('isDelete', false)->first();
                        $details->workflowInstanceId = (!empty($leaveData['workflowInstanceId'])) ? $leaveData['workflowInstanceId'] : null;
                    }

                    //check whether leave has pending leave cancel leave requests
                    $hasPendingCancelLeaveRequests = $this->checkHasPendingCancelLeaveRequests($details);

                    //check leave request has covering person
                    $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
                    ->leftJoin('employee','employee.id','=','leaveCoveringPersonRequests.coveringEmployeeId')
                    ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $details->id)
                    ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                    ->first();
            
                    if (is_null($coveringPersonRequestData)) {
                        $details->hasPendingCoveringPersonRequests = false;
                        $details->manageCoveringPerson = false;
                        $details->coveringPersonRequestsData = [];
                    } else {
                        $details->hasPendingCoveringPersonRequests = ($coveringPersonRequestData->state === 'PENDING') ?  true : false;
                        $details->coveringPersonRequestsData = $coveringPersonRequestData;
                        $details->manageCoveringPerson = true;

                        switch ($coveringPersonRequestData->state) {
                            case 'PENDING':
                                $details->coveringPersonRequestsData->statusLabel = "Pending";
                                $details->coveringPersonRequestsData->tagFontColor = "#F8A325";
                                $details->coveringPersonRequestsData->tagColor = "#FFEFD8";
                                break;
                            case 'APPROVED':
                                $details->coveringPersonRequestsData->statusLabel = "Approved";
                                $details->coveringPersonRequestsData->tagFontColor = "#389e0d";
                                $details->coveringPersonRequestsData->tagColor = "#F6FCED";
                                break;
                            case 'DECLINED':
                                $details->coveringPersonRequestsData->statusLabel = "Declined";
                                $details->coveringPersonRequestsData->tagFontColor = "#CF1322";
                                $details->coveringPersonRequestsData->tagColor = "#FFF1F0";
                                break;
                            case 'CANCELED':
                                $details->coveringPersonRequestsData->statusLabel = "Canceled";
                                $details->coveringPersonRequestsData->tagFontColor = "#1D39C4";
                                $details->coveringPersonRequestsData->tagColor = "#F0F5FF";
                                break;
                            default:
                                $details->coveringPersonRequestsData->statusLabel = null;
                                $details->coveringPersonRequestsData->tagFontColor = null;
                                $details->coveringPersonRequestsData->tagColor = null;
                                break;
                        }
                    }

                    if ($details->manageCoveringPerson) {
                        $details->canCancelLeaveRequest = (!$hasPendingCancelLeaveRequests && ($relatedUser === $details->createdBy || $relatedUser === $requestedUser->id) && !$result[$key]->isInFinalFaliureState) ? true : false;
                    } else {
                        $details->canCancelLeaveRequest = (!$hasPendingCancelLeaveRequests && ($relatedUser === $value['createdBy'] || $relatedUser === $requestedUser->id) && !$result[$key]->isInFinalFaliureState) ? true : false;
                    }

                    $details->canShowApprovalLevel = true;

                    if ($leaveData['currentState'] == 4 && !empty($leaveData['workflowInstanceId'])) {
                        $instanceApproveLevel =  DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', $leaveData['workflowInstanceId'])->where('levelStatus', 'CANCELED')->where('levelSequence','!=',0)->first();

                        $details->canShowApprovalLevel = !is_null($instanceApproveLevel) ? true : false;
                    }
                }

                
                if ($value['contextId'] == 3) {
                    $timeChangeRequestModel = $this->getModel('attendanceTimeChange', true);
                    $timeChangeData = (array)$this->store->getById($timeChangeRequestModel, $details->id);
                    if (empty($timeChangeData)) {
                        return $this->error(404, Lang::get('workflowMessages.basic.ERR_NOT_EXIST'), null);
                    }
                    $details->currentState = $value['currentStateId'];
                    $details->workflowInstanceId = (!empty($timeChangeData['workflowInstanceId'])) ? $timeChangeData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 4) {
                    $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                    $shortLeaveData = (array)$this->store->getById($shortLeaveRequestModel, $details->id);
                    if (empty($shortLeaveData)) {
                        return $this->error(404, Lang::get('workflowMessages.basic.ERR_NOT_EXIST'), null);
                    }
                    $result[$key]->shortLeaveRequestId = $details->id;  
                    //check whether leave has pending leave cancel leave requests
                    $hasPendingCancelShortLeaveRequests = $this->checkHasPendingCancelShortLeaveRequests($details);


                    $details->canCancelShortLeaveRequest = (!$hasPendingCancelShortLeaveRequests && ($relatedUser === $value['createdBy'] || $relatedUser === $requestedUser->id) && !$result[$key]->isInFinalFaliureState) ? true : false;

                    $initialState =  1;
                    $details->isInInitialState = true;
                    if ($shortLeaveData['currentState'] != $initialState) {
                        $details->isInInitialState = false;
                    } else {

                        if ($shortLeaveData['workflowInstanceId']) {
                            //get instance related levels ids
                            $instanceApproverLevelIdArray= DB::table('workflowInstanceApprovalLevel')
                            ->where('workflowInstanceApprovalLevel.workflowInstanceId', '=', $shortLeaveData['workflowInstanceId'])
                            ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)
                            ->pluck('workflowInstanceApprovalLevel.id')->toArray();

                            if (sizeof($instanceApproverLevelIdArray) > 0)  {
                                //check whether has any actions peroform on that 
                                $instanceApproverLevelDetailCount= DB::table('workflowInstanceApprovalLevelDetail')
                                ->whereIn('workflowInstanceApprovalLevelDetail.workflowInstanceApproverLevelId', $instanceApproverLevelIdArray)->count();

                                $details->isInInitialState = ($instanceApproverLevelDetailCount > 0) ? false : true;
                            }
                        }

                    }

                    $details->canShowApprovalLevel = true;

                    if ($shortLeaveData['currentState'] == 4 && !empty($shortLeaveData['workflowInstanceId'])) {
                        $instanceApproveLevel =  DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', $shortLeaveData['workflowInstanceId'])->where('levelStatus', 'CANCELED')->where('levelSequence','!=',0)->first();

                        $details->canShowApprovalLevel = !is_null($instanceApproveLevel) ? true : false;
                    }

                    $details->currentState = $shortLeaveData['currentState'];
                    $details->workflowInstanceId = (!empty($shortLeaveData['workflowInstanceId'])) ? $shortLeaveData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 5) {
                    $workshiftModel =  $this->getModel('workShifts', true);
                    $newShiftData = (array) $this->store->getById($workshiftModel, $details->newShiftId);
                    $currentShiftData = (array) $this->store->getById($workshiftModel, $details->currentShiftId);

                    $shiftChangeRequestModel =  $this->getModel('shiftChangeRequest', true);
                    $shiftRequestData = (array) $this->store->getById($shiftChangeRequestModel, $details->id);

                    $details->currentShiftName = (!empty($currentShiftData)) ? $currentShiftData['name'] : '-';
                    $details->newShiftName = $newShiftData['name'];
                    $details->currentState = $shiftRequestData['currentState'];
                    $details->workflowInstanceId = (!empty($shiftRequestData['workflowInstanceId'])) ? $shiftRequestData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 6) {
                    $cancelLeaveRequestModel =  $this->getModel('cancelLeaveRequest', true);
                    $cancelLeaveRequestData = (array) $this->store->getById($cancelLeaveRequestModel, $details->id);

                    $leaveRequestModel = $this->getModel('leaveRequest', true);
                    $leaveData = (array)$this->store->getById($leaveRequestModel, $details->leaveRequestId);


                    $details->currentState = $cancelLeaveRequestData['currentState'];
                    $details->fromDate = $leaveData['fromDate'];
                    $details->toDate = $leaveData['toDate'];
                    $details->leaveTypeId = $leaveData['leaveTypeId'];
                    $details->workflowInstanceId = (!empty($cancelLeaveRequestData['workflowInstanceId'])) ? $cancelLeaveRequestData['workflowInstanceId'] : null;

                }

                if ($value['contextId'] == 7) {
                    $resignationTypeData = (array)$this->store->getById($this->resignationTypeModel, $details->resignationTypeId);
                    $details->resignationType = (!is_null($resignationTypeData) && !empty($resignationTypeData)) ?  $resignationTypeData['name'] : '-';
                }

                if ($value['contextId'] == 8) {
                    $cancelShortLeaveRequestModel =  $this->getModel('cancelShortLeaveRequest', true);
                    $cancelShortLeaveRequestData = (array) $this->store->getById($cancelShortLeaveRequestModel, $details->id);

                    $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                    $leaveData = (array)$this->store->getById($shortLeaveRequestModel, $details->shortLeaveRequestId);


                    $details->currentState = $cancelShortLeaveRequestData['currentState'];
                    $details->workflowInstanceId = (!empty($cancelShortLeaveRequestData['workflowInstanceId'])) ? $cancelShortLeaveRequestData['workflowInstanceId'] : null;
                }

                if ($value['contextId'] == 9) {
                    $claimRequestModel =  $this->getModel('claimRequest', true);
                    $claimRequestData = (array) $this->store->getById($claimRequestModel, $details->id);

                    $details->currentState = $claimRequestData['currentState'];
                    $details->workflowInstanceId = (!empty($claimRequestData['workflowInstanceId'])) ? $claimRequestData['workflowInstanceId'] : null;
                }

                if ($value['contextId'] == 10) {
                    $postOtRequestModel =  $this->getModel('postOtRequest', true);
                    $postOtRequestData = (array) $this->store->getById($postOtRequestModel, $details->id);

                    $details->currentState = $postOtRequestData['currentState'];
                    $details->workflowInstanceId = (!empty($postOtRequestData['workflowInstanceId'])) ? $postOtRequestData['workflowInstanceId'] : null;
                }

                $result[$key]->details = json_encode($details);


                // set display heading 1
                $result[$key]->displayHeading1 = $this->generateHeading1($value['contextId'], $details);
                $result[$key]->displayHeading2 = $this->generateHeading2($value['contextId'], $details);

                $createdAt = isset($details->createdAt) && !empty($details->createdAt) ? $details->createdAt : $value['createdAt'];
                $result[$key]->updatedAt =  $this->getFormattedDateForList($value['updatedAt'], $timeZone);
                $result[$key]->requestedOn = $this->getFormattedDateForList($createdAt, $timeZone);

                $dataArray[] = $result[$key];
            }

            $responce = new stdClass();
            $responce->count = $totalRecordCount;
            $responce->sheets = $dataArray;
            $responce->success = true;

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_SINGLE_RETRIVE'), $responce);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    private function generateHeading1($contextId, $details) {
        $details = (array) $details;
        $heading = '';
        switch ($contextId) {
            case '1':
                $heading = 'Request for ';
                if ($details['relateAction'] == 'create') {
                    $heading .= 'creating record relate to ';
                } elseif ($details['relateAction'] == 'update') {
                    $heading .= 'updating record relate to ';
                } elseif ($details['relateAction'] == 'delete') {
                    $heading .= 'deleting record relate to ';
                } 

                // set related tab to heading
                $heading .= $this->camelCaseToGeneralText($details['tabName']) .' details section';
                break;
            case '2':
                //get related leave Type
                $leaveTypeModel =  $this->getModel('leaveType', true);
                $leaveTypeData = (array) $this->store->getById($leaveTypeModel, $details['leaveTypeId']);
                $numberOfDays = (!empty($details['numberOfLeaveDates'])) ? $details['numberOfLeaveDates'] : '-';
                
                $heading = 'Type :'.$leaveTypeData['name'].' | Start Date : '. date('d-m-Y', strtotime($details['fromDate'])).' | End Date : '. date('d-m-Y', strtotime($details['toDate'])).' | Days : '.$numberOfDays;
                break;
            case '3':
                $inDate = $details['inDateTime'] ? date('d-m-Y', strtotime($details['inDateTime'])) : '-';
                $inTime = $details['inDateTime'] ? date('h:i A', strtotime($details['inDateTime'])) : '-';
                $outDate = $details['outDateTime'] ? date('d-m-Y', strtotime($details['outDateTime'])) : '-';
                $outTime = $details['outDateTime'] ? date('h:i A', strtotime($details['outDateTime'])) : '-';
             
                $heading = 'In Date : '. $inDate.' | Clock In : '. $inTime. ' | Out Date : '.$outDate.' | Clock Out : '. $outTime;
                break;
            case '4':
                //get related short leave Type
                $heading = 'Type : Short Leave | Date : '. date('d-m-Y', strtotime($details['date'])).' | From : '. Carbon::parse($details['fromTime'])->format('g:i A') .' | To Time : '. Carbon::parse($details['toTime'])->format('g:i A');
                break;
            case '5':
                $workshiftModel =  $this->getModel('workShifts', true);
                $newShiftData = (array) $this->store->getById($workshiftModel, $details['newShiftId']);
                $currentShiftData = (array) $this->store->getById($workshiftModel, $details['currentShiftId']);
                $newShift = (!empty($newShiftData['name'])) ? $newShiftData['name'] : '-';
                $currentShift = (!empty($currentShiftData['name'])) ? $currentShiftData['name'] : '-';
                $heading = 'New Shift: '. $newShift.' | Current Shift: '. $currentShift;

                break;
            case '6':
                $leaveRequestModel =  $this->getModel('leaveRequest', true);
                $leaveData = (array) $this->store->getById($leaveRequestModel, $details['leaveRequestId']);
                $leaveTypeModel =  $this->getModel('leaveType', true);
                $leaveTypeData = (array) $this->store->getById($leaveTypeModel, $leaveData['leaveTypeId']);

                $cancelCount = 0;
                foreach ($details['cancelDatesDetails'] as $key => $cancelData) {
                    $cancelData = (array) $cancelData;
                    if ($cancelData['leavePeriodTypeLabel'] == 'Full Day') {
                        $cancelCount += 1;
                    } elseif($cancelData['leavePeriodTypeLabel'] == 'Half Day') {
                        $cancelCount += 0.5;
                    } else {
                        $cancelCount += 0;
                    }
                }

                $numberOfDays = $cancelCount;
                $heading = 'Type :'.$leaveTypeData['name'].' | Start Date : '. date('d-m-Y', strtotime($details['fromDate'])).' | End Date : '. date('d-m-Y', strtotime($details['toDate'])).' | Cancel Days : '.$numberOfDays;
                break;
            case '7':
                $lastWorkingDate = !empty($details['lastWorkingDate']) ? date('d-m-Y', strtotime($details['lastWorkingDate'])) : '-';
                $heading = 'Handover Date : '. date('d-m-Y', strtotime($details['resignationHandoverDate'])).' | Effective Date : '. date('d-m-Y', strtotime($details['effectiveDate'])).' | Last Working Date : '.$lastWorkingDate;
                break;
            case '8':
                $heading = 'Date : '. date('d-m-Y', strtotime($details['date'])).' | From Time: '. Carbon::parse($details['fromTime'])->format('g:i A') .' | To Time : '. Carbon::parse($details['toTime'])->format('g:i A');
                break;
            case '9':
                $claimTypeModel =  $this->getModel('claimType', true);
                $claimTypeData = (array) $this->store->getById($claimTypeModel, $details['claimTypeId']);

                $financialYearModel =  $this->getModel('financialYear', true);
                $financialYearData = (array) $this->store->getById($financialYearModel, $details['financialYearId']);

                $heading = (!is_null($details['claimMonth'])) ? 'Claim Type : '. $claimTypeData['typeName'].' | Financial Year: '. $financialYearData['financialDateRangeString'].' | Claim Month: '. $details['claimMonth'] : 'Claim Type : '. $claimTypeData['typeName'].' | Financial Year: '. $financialYearData['financialDateRangeString'];
                break;
            case '10':
                $postOtRequestModel =  $this->getModel('postOtRequest', true);
                $postOtRequesteData = (array) $this->store->getById($postOtRequestModel, $details['id']);

                $yearAndMonth = $postOtRequesteData['month'];
                $arr = explode('/',$yearAndMonth);
                $year = $arr[0];
                $month = $arr[1];
                $totalRequestedOt =  gmdate("H:i", $postOtRequesteData['totalRequestedOtMins'] * 60);

                $heading = 'Year : '. $year .' | Month: '. $month.' | Total Requested OT Hours: '. $totalRequestedOt;
                break;
            
            default:
                break;
        }

        return $heading;
    }

    private function generateHeading2($contextId, $details) {
        $details = (array) $details;
        $heading2 = '';
        switch ($contextId) {
            case '1':
                $heading2 = '';
                break;
            case '2':
                $heading2 = (!empty($details['reason'])) ? 'Reason : '.$details['reason'] : 'Reason : -';
                break;
            case '3':
                $heading2 = (!empty($details['reason'])) ? 'Reason : '.$details['reason'] : 'Reason : -';
                break;
            case '4':
                $heading2 = (!empty($details['reason'])) ? 'Reason : '.$details['reason'] : 'Reason : -';
                break;
            case '5':
                $heading2 = (!empty($details['reason'])) ? 'Reason : '.$details['reason'] : 'Reason : -';
                break;
            case '6':
                $heading2 = (!empty($details['cancelReason'])) ? 'Reason of Cancellation : '.$details['cancelReason'] : 'Reason of Cancellation : -';
                break;
            case '7':
                $heading2 = (!empty($details['resignationReason'])) ? 'Reason : '.$details['resignationReason'] : 'Reason : -';
                break;
            case '9':
                $heading2 = 'Total Receipt Amount : '.$details['totalReceiptAmount'];
                break;
            case '10':
                $postOtRequestDetailCount = DB::table('postOtRequestDetail')->where('postOtRequestId',  $details['id'])->count();
                $heading2 = 'Num Of Requested Dates : '.$postOtRequestDetailCount;
                break;
            default:
                break;
        }

        return $heading2;
    }


    public function getWorkflowByKeyword($keyword)
    {
        try {

            $workflow = $this->store->getFacade()::table('workflowStateTransitions')->where('workflowAction', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_ALL_RETRIVE'), $workflow);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    private function labelToCamelCase($string)
    {
        $str = str_replace(' ', '',  ucwords($string));
        $str[0] = strtolower($str[0]);
        return $str;
    }

    private function camelCaseToGeneralText($string, $us = " ")
    {
        return ucwords(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',
            $us,
            $string
        ));
    }



    /**
     * Following function generates the data for workflow filter .
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "success",
     *      $data => null
     * 
     */

    public function getWorkflowFilterOptions()
    {
        try {

            $queryBuilder = DB::table('workflowInstance')
                ->distinct()
                ->select(
                    'workflowState.stateName as priorStateName',
                )
                ->leftJoin('workflowState', 'workflowInstance.currentStateId', '=', 'workflowState.id')
                ->leftJoin('workflowDefine', 'workflowInstance.workflowId', '=', 'workflowDefine.id')
                ->leftJoin('workflowContext', 'workflowContext.id', '=', 'workflowDefine.contextId')
                ->where('workflowInstance.isDelete', '=', false);
            $result = $queryBuilder->pluck('priorStateName');

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_SINGLE_RETRIVE'), $result);

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_ALL_RETRIVE'), $workflow);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function generates the data for workflow filter .
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "success",
     *      $data => null
     * 
     */

    public function getLeaveRequestRelatedStates($sorter = null)
    {
        try {
            $queryBuilder = DB::table('leaveRequest')
                ->distinct()
                ->select(
                    'workflowState.stateName as priorStateName',
                    'workflowState.label as priorStateLabel',
                    'workflowState.id as workflowStateId',
                )
                ->leftJoin('workflowState', 'leaveRequest.currentState', '=', 'workflowState.id');
                // ->leftJoin('workflowContext', 'workflowContext.id', '=', 'workflowInstance.contextId')
                // ->where([['workflowInstance.isDelete', '=', false], ['workflowContext.id', '=', 2]]);
            
            if (!is_null($sorter)) {
                $sorter = (array) json_decode($sorter);
                $queryBuilder->orderBy($sorter['name'], $sorter['order']);
            }

            $result = $queryBuilder->get();

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_SINGLE_RETRIVE'), $result);

            // return $this->success(200, Lang::get('workflowMessages.basic.SUCC_ALL_RETRIVE'), $workflow);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function return all states related with short leave requests.
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "success",
     *      $data => []
     * 
     */

    public function getShortLeaveRequestRelateWorkflowStates($sorter = null)
    {
        try {
            $queryBuilder = DB::table('shortLeaveRequest')
            ->distinct()
                ->select(
                    'workflowState.stateName as priorStateName',
                    'workflowState.label as priorStateLabel',
                    'workflowState.id as workflowStateId',
                )
                ->leftJoin('workflowState', 'shortLeaveRequest.currentState', '=', 'workflowState.id');

            if (!is_null($sorter)) {
                $sorter = (array) json_decode($sorter);
                $queryBuilder->orderBy($sorter['name'], $sorter['order']);
            }

            $result = $queryBuilder->get();

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_SINGLE_RETRIVE'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function updates the status of a workflow.
     * 
     * @param $id workflow id
     * @param $Workflow array containing Workflow data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflow updated successfully.",
     *      $data => null
     * 
     */

    public function updateWorkflow($id, $workflow)
    {
        try {
            DB::beginTransaction();
            $queryBuilder = $this->store->getFacade();
            $hasPermitted = false;

            $workflowData = $queryBuilder::table('workflowInstance')
                ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
                ->leftJoin('workflowDefine', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
                ->where('workflowInstance.id', $id)
                ->first();

            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $sessionUserData = $this->session->getUser();
            

            if (!empty($requestedEmployeeId)) {
                $userData = $this->getEmployeeRelatedUserByEmployeeId($requestedEmployeeId);
                $userData = (array) $userData;
            } else {
                $userData['id'] = $this->session->getUser()->id;
            }

            $userDetails = DB::table('user')->where('id', '=', $userData['id'])->first();

            $workflowDefineData = (array) $this->store->getById($this->workflowDefineModel, $workflow['workflowId']);
            if (is_null($workflowDefineData)) {
                return $this->error(404, Lang::get('workflowMessages.basic.ERR_UPDATE'), $id);
            }

            $levelApproverType = NULL;
            $emailActionId = null;
            $isCancelActionFromRequestEmployee = false;
            // $permittedRoleId = null;
            // $managerPermittedActions = $this->getManagerPermittedActions($workflow['workflowId'], 3);

            // $priorStateBeforeUpdate = $workflowData->priorState;

            // if ($workflowData->employeeId == $requestedEmployeeId) { // if own request treat as employee
            //     $permittedRoleId = 2;
            // } else if (!empty($this->session->getUser()->managerRoleId) && $requestedEmployeeId == $this->getManagerId($workflowData->employeeId) && sizeof($managerPermittedActions) > 0 && in_array($workflow['actionId'], $managerPermittedActions)) { // if manager
            //     $permittedRoleId = 3;
            // } else if (!empty($this->session->getUser()->adminRoleId)) { // if admin
            //     $permittedRoleId = 1;
            // }

            // $hasPermitted = $this->hasPermittedToPerformAction($permittedRoleId, $workflowData->workflowId, $workflow['actionId'], $requestedEmployeeId);

            $instanceAprovalLevelData = null;
            $employeeId = $workflowData->employeeId;
            // if own request treat as employee
            if ($requestedEmployeeId == $workflowData->employeeId) {
                if ($workflowDefineData['isAllowToCancelRequestByRequester'] && $workflow['actionId'] == 4) {
                    //state id == 1 mean its in the pending state
                    if ($workflowData->currentApproveLevelSequence == 1 && $workflowData->currentStateId == 1) {
                        // get instance relate approval level
                        $instanceAprovalLevel = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $id)->where('levelSequence', $workflowData->currentApproveLevelSequence)->first();
                        if (!empty($instanceAprovalLevel)) {
                            //check whether any one perform a action for this level of the request
                            $leaveRequestDataActionPeroformDetailCount = DB::table('workflowInstanceApprovalLevelDetail')->where('workflowInstanceApproverLevelId', '=', $instanceAprovalLevel->id)->count();
    
                            if ($leaveRequestDataActionPeroformDetailCount == 0) {
                                $hasPermitted = true;
                                $isCancelActionFromRequestEmployee = true;
                            }
                        }
                    }
                }
                
            } else {

                // get instance relate approval level
                $instanceAprovalLevelData = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $id)->where('levelSequence', $workflowData->currentApproveLevelSequence)->first();
                $instanceAprovalLevelData = (array) $instanceAprovalLevelData;
                
                //state id == 1 mean its in the pending state (if current state is not equal to pending can not to any actions)
                if ($workflowData->currentStateId == 1) {
                    if ($instanceAprovalLevelData['levelStatus'] == 'PENDING' && !$instanceAprovalLevelData['isLevelCompleted']) {

                        //check whether loggedUser has permissions to perform actions
                        $permittedPerformActions = $this->getLevelPermissionsForPerformActions($instanceAprovalLevelData, $sessionUserData, $workflowDefineData, $requestedEmployeeId, $workflowData->employeeId);
                        if (sizeof($permittedPerformActions) > 0) {

                            //get workflow level config
                            $workflowLevelConfig = DB::table('workflowApprovalLevel')->where('workflowId', '=', $workflow['workflowId'])->where('levelSequence', $workflowData->currentApproveLevelSequence)->first();
                            $levelApproverType = (!empty($workflowLevelConfig->levelType)) ? $workflowLevelConfig->levelType : null;

                            $hasPermitted = true;
                        }
                    }
                }
            }

            $approvalLevelPerformType = 'OR';

            $queryBuilder = DB::table('workflowAction')
                ->select(
                    'workflowAction.*'
                )
                ->where([['workflowAction.isDelete', '=', false], ['workflowAction.id', '=', $workflow['actionId']]]);
            $performActions = $queryBuilder->first();

           
            if (!$hasPermitted) {
                return $this->error(403, Lang::get('workflowMessages.basic.ERR_NOT_PERMITTED_TO_PERFORM'), null);
            }

            if ($levelApproverType == NULL && $isCancelActionFromRequestEmployee) {
                //this mean this is a cancel action that perform by requested employee

                // get instance relate approval level
                $instanceAprovalLevelData = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $id)->where('levelSequence', 0)->first();
                $instanceAprovalLevelData = (array) $instanceAprovalLevelData;


                //update the workflow instace current status to canceled
                $currentPriorStateOfWfInstance = 4;
                $updateWorkflowInstancDataset = [
                    'currentStateId' => 4 // id no 4 means camceled state id
                ];
                $updateInstanceCurrentStatus = DB::table('workflowInstance')->where('id', '=', $id)->update($updateWorkflowInstancDataset);

                //add new record to instance approval level detail table
                $instanceApprovalLevelDetailRecord = [
                    'workflowInstanceApproverLevelId' => $instanceAprovalLevelData['id'],
                    'performAction' => $workflow['actionId'],
                    'approverComment' => $workflow['approverComment'],
                    'performBy' => $userData['id'],
                    'performAt' => Carbon::now()->toDateTimeString()
                ];

                $emailActionId = $workflow['actionId'];

                $wfInstanceApprovalLevelDetailIdForCreate = DB::table('workflowInstanceApprovalLevelDetail')->insertGetId($instanceApprovalLevelDetailRecord);

                //update instance level data
                $insanceLevelDataset = [
                    'levelStatus' => 'CANCELED',
                    'isLevelCompleted' => false
                ];
                $updateInstanceLevelDataset = DB::table('workflowInstanceApprovalLevel')->where('id', '=', $instanceAprovalLevelData['id'])->update($insanceLevelDataset);
                
                //perform  the cancel proceess according to the context
                $this->performFailierProcessForWorkflowRequest($workflowDefineData, $id, $workflowData, $currentPriorStateOfWfInstance);

            } else {

                $workflowLevelConfig = DB::table('workflowApprovalLevel')->where('workflowId', '=', $workflow['workflowId'])->where('levelSequence', $workflowData->currentApproveLevelSequence)->first();

                if ($approvalLevelPerformType == 'OR' && !is_null($instanceAprovalLevelData)) {
                    if ($performActions->isSuccessAction) {
                        $currentPriorStateOfWfInstance = 2;

                        // update post ot details when context id = 10
                        if ($workflowDefineData['contextId'] == 10) {
                            foreach ($workflow['postOtDetails'] as $postOtkey => $attendanceRecord) {
                                $attendanceRecord = (array) $attendanceRecord;
                                $commentList = $attendanceRecord['approveUserCommentList'];

                                if (!empty($attendanceRecord['approveUserComment'])) {
                                    $commentObj = [
                                        'level' => $workflowLevelConfig->levelName,
                                        'comment' => $attendanceRecord['approveUserComment'],
                                        'performBy' => $userDetails->firstName.' '.$userDetails->lastName
                                    ];

                                    array_push($commentList, $commentObj);
                                }

                                $postOtRequestUpdatedDetails = [
                                    'otDetails' => json_encode($attendanceRecord['otDetails']),
                                    'approveUserComment' => json_encode($commentList),
                                ];
            
                                $updateOtDetail = DB::table('postOtRequestDetail')->where('id',  $attendanceRecord['id'])->update($postOtRequestUpdatedDetails);
                            }
                        }

                        if ($instanceAprovalLevelData['isHaveNextLevel']) {

                            $updateWorkflowInstancDataset = [
                                'currentApproveLevelSequence' => $workflowData->currentApproveLevelSequence + 1
                            ];

                            //update the workflow instace current level
                            $updateInstanceCurrentLevelSequence = DB::table('workflowInstance')->where('id', '=', $id)->update($updateWorkflowInstancDataset);

                            //add new record to instance approval level detail table
                            $instanceApprovalLevelDetailRecord = [
                                'workflowInstanceApproverLevelId' => $instanceAprovalLevelData['id'],
                                'performAction' => $workflow['actionId'],
                                'approverComment' => $workflow['approverComment'],
                                'performBy' => $userData['id'],
                                'performAt' => Carbon::now()->toDateTimeString()
                            ];

                            $emailActionId = 2;

                            $wfInstanceApprovalLevelDetailIdForCreate = DB::table('workflowInstanceApprovalLevelDetail')->insertGetId($instanceApprovalLevelDetailRecord);
                                    
                            //update instance level data
                            $insanceLevelDataset = [
                                'levelStatus' => 'APPROVED',
                                'isLevelCompleted' => true
                            ];
                            $updateInstanceLevelDataset = DB::table('workflowInstanceApprovalLevel')->where('id', '=', $instanceAprovalLevelData['id'])->update($insanceLevelDataset);


                        } else {
                            //update the workflow instace current status
                            $updateWorkflowInstancDataset = [
                                'currentStateId' => 2 // id no 2 means approve state id
                            ];
                            $updateInstanceCurrentStatus = DB::table('workflowInstance')->where('id', '=', $id)->update($updateWorkflowInstancDataset);

                            //add new record to instance approval level detail table
                            $instanceApprovalLevelDetailRecord = [
                                'workflowInstanceApproverLevelId' => $instanceAprovalLevelData['id'],
                                'performAction' => $workflow['actionId'],
                                'approverComment' => $workflow['approverComment'],
                                'performBy' => $userData['id'],
                                'performAt' => Carbon::now()->toDateTimeString()
                            ];

                            $emailActionId = 5;

                            $wfInstanceApprovalLevelDetailIdForCreate = DB::table('workflowInstanceApprovalLevelDetail')->insertGetId($instanceApprovalLevelDetailRecord);


                            //update instance level data
                            $insanceLevelDataset = [
                                'levelStatus' => 'APPROVED',
                                'isLevelCompleted' => true
                            ];
                            $updateInstanceLevelDataset = DB::table('workflowInstanceApprovalLevel')->where('id', '=', $instanceAprovalLevelData['id'])->update($insanceLevelDataset);

                            //perform  the final sucess proceess according to the context
                            $this->performSuccessProcessForWorkflowRequest($workflowDefineData, $id, $workflowData);
                            
                        }
  
                    } else {

                        //update the workflow instace current status to rejected
                        $currentPriorStateOfWfInstance = 3;
                        $updateWorkflowInstancDataset = [
                            'currentStateId' => 3 // id no 2 means rejected state id
                        ];
                        $updateInstanceCurrentStatus = DB::table('workflowInstance')->where('id', '=', $id)->update($updateWorkflowInstancDataset);

                        // update post ot details when context id = 10
                        if ($workflowDefineData['contextId'] == 10) {
                            foreach ($workflow['postOtDetails'] as $postOtkey => $attendanceRecord) {
                                $attendanceRecord = (array) $attendanceRecord;
                                $commentList = $attendanceRecord['approveUserCommentList'];


                                if (!empty($attendanceRecord['approveUserComment'])) {
                                    $commentObj = [
                                        'level' => $workflowLevelConfig->levelName,
                                        'comment' => $attendanceRecord['approveUserComment'],
                                        'performBy' => $userDetails->firstName.' '.$userDetails->lastName
                                    ];

                                    array_push($commentList, $commentObj);
                                }


                                $postOtRequestUpdatedDetails = [
                                    'approveUserComment' => json_encode($commentList),
                                ];
            
                                $updateOtDetail = DB::table('postOtRequestDetail')->where('id',  $attendanceRecord['id'])->update($postOtRequestUpdatedDetails);
                            }
                        }

                        //add new record to instance approval level detail table
                        $instanceApprovalLevelDetailRecord = [
                            'workflowInstanceApproverLevelId' => $instanceAprovalLevelData['id'],
                            'performAction' => $workflow['actionId'],
                            'approverComment' => $workflow['approverComment'],
                            'performBy' => $userData['id'],
                            'performAt' => Carbon::now()->toDateTimeString()
                        ];
                        $emailActionId = $workflow['actionId'];

                        $wfInstanceApprovalLevelDetailIdForCreate = DB::table('workflowInstanceApprovalLevelDetail')->insertGetId($instanceApprovalLevelDetailRecord);

                        //update instance level data
                        $insanceLevelDataset = [
                            'levelStatus' => 'REJECTED',
                            'isLevelCompleted' => false
                        ];
                        $updateInstanceLevelDataset = DB::table('workflowInstanceApprovalLevel')->where('id', '=', $instanceAprovalLevelData['id'])->update($insanceLevelDataset);
                        
                        //perform  the final failier proceess according to the context
                        $this->performFailierProcessForWorkflowRequest($workflowDefineData, $id, $workflowData, $currentPriorStateOfWfInstance);
                        
                    }

                }
   
            }

            DB::commit();

            if (!is_null($workflow['actionId'])) { 
                $emailSend = $this->emailNotificationService->sendEmailNotificationsForRelatedPersons($emailActionId, $employeeId, $id, $instanceApprovalLevelDetailRecord, $workflowDefineData['contextId']);
            } 
            
            $staeMessage = null;

            if ($currentPriorStateOfWfInstance == 3) {
                $staeMessage = 'workflowMessages.basic.SUCC_REQUEST_REJECTED';
            } elseif ($currentPriorStateOfWfInstance == 2) {
                $staeMessage = 'workflowMessages.basic.SUCC_REQUEST_APPROVE';
            } else {
                $staeMessage = 'workflowMessages.basic.SUCC_REQUEST_CANCELLED';
            }

            return $this->success(200, Lang::get($staeMessage), $workflow);


        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_UPDATE'), null);
        }
    }

    private function performSuccessProcessForWorkflowRequest($workflowDefineData, $id, $workflowData) 
    {
        switch ($workflowDefineData['contextId']) {
            case 1:
                //Sucess Action For Profile Update Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employee = json_decode($instanceResult->details, true);
                $employee  = (array) $employee; 
                $currentEmployeeId = $instanceResult->employeeId;
                $employeeId = $instanceResult->employeeId;
                    
                $res = $this->performProfileUpdateWfSucessState($employee, $currentEmployeeId);

                if ($res['error']) {
                    DB::rollback();
                    return $this->error($res['statusCode'], $res['message'], $currentEmployeeId);
                }
                break;
            case 2:
                //Sucess Action For Apply Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                //update leave request current state
                $leaveRequestModel = $this->getModel('leaveRequest', true);
                $leaveData['currentState'] = 2;
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($leaveRequestModel, $oldDetail['id'], $leaveData);


                $instanceResult = $queryBuilder->first();
                $leaveData = json_decode($instanceResult->details, true);
                $leaveData  = (array) $leaveData; 
                $employeeId = $instanceResult->employeeId;

                //update leave Request Details
                $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
                    ->where('leaveRequestId', $leaveData['id'])
                    ->where('status', 'PENDING')
                    ->get()->toArray();
                
                foreach ($leaveRequestDetails as $key => $leaveRequestDetail) {
                    $leaveRequestDetail = (array) $leaveRequestDetail;

                    $leaveRequestDetail['status'] = 'APPROVED';
                    $updateDetailStatus = $this->store->updateById($this->leaveRequestDetailModel, $leaveRequestDetail['id'], $leaveRequestDetail);
                    if (!$updateDetailStatus) {
                        DB::rollback();
                        return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_UPDATE'),$newLeave['id']);
                    }  
                }

                $workCalendarService = new WorkCalendarService ($this->store);
                $leaveTypeService = new LeaveTypeService ($this->store, $this->session);
                $leaveRequestService = new LeaveRequestService($this->store, $this->session, $this->fileStorage, $this, $this->redis, $workCalendarService, $leaveTypeService);
                $result =  $leaveRequestService->setLeaveEntitlementAllocations($leaveData['id']);

                if ($result['error']) {
                    DB::rollback();
                    return $this->error($result['statusCode'], $result['message'], $id);
                }

                $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
                    ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $leaveData['id'])
                    ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                    ->first();

                if (!is_null($coveringPersonRequestData)) {

                    $emailData = [
                        'fromDate' => $leaveData['fromDate'],
                        'toDate' => $leaveData['toDate'],
                        'numOfDays' => $leaveData['numberOfLeaveDates'],
                        'coveringEmployeeId' => $coveringPersonRequestData->coveringEmployeeId,
                        'employeeId' => $leaveData['employeeId']
                    ];

                    //send email to covering person when this particular leave is relate with covering person
                    $this->sendEmailForLeaveCoveringPerson('leaveApproved', $emailData);
                } 
                break;
            case 3:
                //Sucess Action For Attendance Time Change Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $dates = [];
                $instanceResult = $queryBuilder->first();
                $chageTimeData = json_decode($instanceResult->details, true);
                $employeeId = $instanceResult->employeeId;
                $chageTimeData = (array) $chageTimeData;
                $summaryId = $chageTimeData['summaryId'];

                $inDateArr = explode(" ",$chageTimeData['inDateTime']);
                $inDate = (!empty($inDateArr)) ? $inDateArr[0] : null;
                $outDateArr = explode(" ",$chageTimeData['outDateTime']);
                $outDate = (!empty($outDateArr)) ? $outDateArr[0] : null;

                if (!is_null($inDate) && !in_array($inDate, $dates)) {
                    $dates[] = $inDate;
                }

                if (!is_null($outDate) && !in_array($outDate, $dates)) {
                    $dates[] = $outDate;
                }

                //check whether requested date related attendane summary records are locked
                $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $dates);
                
                if ($hasLockedRecords) {
                    DB::rollback();
                    return $this->error(500, Lang::get('attendanceMessages.basic.ERR_CANNOT_APPROVE_TIME_CHANGE_DUE_TO_LOCKED_ATTENDANCE_RECORDS'), null);
                }

                $timeChangeDataset = [
                    "timeChangeId" =>  $chageTimeData['id'],
                    "type" => 2,
                    "shiftId" => 1,
                    "employeeId" => $employeeId,
                    "summaryId" => $summaryId
                ];

                $attendanceService = new AttendanceService($this->store, $this->session, $this);
                $result = $attendanceService->approveAttendanceTime($timeChangeDataset);
                if ($result['error']) {
                    DB::rollback();
                    return $this->error($result['statusCode'], $result['message'], $id);
                }

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $dates
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));

                break;
            case 4:
                //Sucess Action For Apply Short Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in shortLeaveRequest table
                $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                $leaveData['currentState'] = 2;
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($shortLeaveRequestModel, $oldDetail['id'], $leaveData);

                break;
            case 5:
                //Sucess Action For Shift Change Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;
                $chageData = (array)json_decode($instanceResult->details, true);

                $shiftDate = $chageData['shiftDate'];
                $shiftId = $chageData['newShiftId'];


                //set adhoc shift for particular date
                $checkShiftExists = DB::table('adhocWorkshifts')->where('date',$shiftDate)->where('employeeId',$employeeId)->first();
                $workShiftId = $shiftId;
                if (empty($checkShiftExists)) {
                    $adhocWorkShiftId = DB::table('adhocWorkshifts')
                    ->insertGetId([
                        'workshiftId' => $workShiftId, 
                        'date'       => $shiftDate,
                        'employeeId' => $employeeId
                        ]);
                } else {
                    $adhocWorkShift = DB::table('adhocWorkshifts')->where('id',$checkShiftExists->id)->update(['workShiftId' =>   $workShiftId]);
                    $adhocWorkShiftId = $checkShiftExists->id;
                }


                $shiftChangeDates = [];
                $shiftChangeDates[] = $chageData['shiftDate'];

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $shiftChangeDates
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));

                //update current state in shiftChangeRequest table
                $shiftChangeRequestModel = $this->getModel('shiftChangeRequest', true);
                $shiftChangeData['currentState'] = 2;
                $shiftChangeData['relateAdhocShiftId'] = $adhocWorkShiftId;
                $oldDetail = (array) json_decode($workflowData->details);

                $updateCurrentState = $this->store->updateById($shiftChangeRequestModel, $oldDetail['id'], $shiftChangeData);

                break;
            case 6:
                //Sucess Action For Cancel Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;
                $changeData = (array)json_decode($instanceResult->details, true);
                $this->performSuccessProcessForLeaveCancel($changeData, $workflowData);
                break;
            case 7:

                //Sucess Action For Cancel Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;
                $changeData = (array)json_decode($instanceResult->details, true);

                $changeData['effectiveDate'] = $changeData['updatedEffectiveDate'];
                unset($changeData['updatedEffectiveDate']);
                unset($changeData['effectiveDateChangeHistory']);

                $employeeService = new EmployeeService($this->store, $this->session, $this->redis, $this->fileStorage, $this, $this->modelService, $this->autoGenrateIdService, $this->activeDirectory, $this->azureUser);
                $createMultiRecord = $employeeService->createResignationRecordInEmployeeJobTable($employeeId, 'jobs', (array) $changeData);
                break;
            case 8:
                //Sucess Action For Cancel Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;
                $changeData = (array)json_decode($instanceResult->details, true);
                $this->performSuccessProcessForShortLeaveLeaveCancel($changeData, $workflowData);
                break;
            case 9:
                //Sucess Action For Cancel Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in shortLeaveRequest table
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = DB::table('claimRequest')->where('id', $oldDetail['id'])->update(['currentState' => 2]);

                //update claim allocation table
                $relatedAllocationRecord = (array) DB::table('claimAllocationDetail')
                                ->where('employeeId', $employeeId)
                                ->where('financialYearId', $oldDetail['financialYearId'])
                                ->where('claimTypeId', $oldDetail['claimTypeId'])->first();
                if (!empty($relatedAllocationRecord)) {

                    $newUsedAmount = $relatedAllocationRecord['usedAmount'] + $oldDetail['totalReceiptAmount'];
                    $pendingAmount = $relatedAllocationRecord['pendingAmount'] - $oldDetail['totalReceiptAmount'];
                    
                    $updateAllocationData = [
                        'usedAmount' => $newUsedAmount,
                        'pendingAmount' => $pendingAmount
                    ];
    
                    $relatedAllocationRecord = (array) DB::table('claimAllocationDetail')
                                    ->where('id', $relatedAllocationRecord['id'])
                                    ->update($updateAllocationData);
                }

                break;
            case 10:
                //Sucess Action For Cancel Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = DB::table('postOtRequest')->where('id', $oldDetail['id'])->update(['currentState' => 2]);
                $status = 'APPROVED';

                //get related post ot attendance details
                $relatedPostOtDetails = DB::table('postOtRequestDetail')->where('postOtRequestId', $oldDetail['id'])->get();


                if (!is_null($relatedPostOtDetails)) {
                    foreach ($relatedPostOtDetails as $detailKey => $detailRecord) {
                        $detailRecord = (array) $detailRecord;
                        $otDetail = json_decode($detailRecord['otDetails']);
                        $apporveOtDetails = $otDetail->approvedOtDetails;
                        $summaryId = $detailRecord['summaryId'];
                        $totalApprovedOtCount = 0;

                        foreach ($apporveOtDetails as $approveKey => $approvePayTypeCount) {
                            //get related pay type by pay code

                            $payType = DB::table('payType')->where('code', $approveKey)->first();
                            $approveCount = 0;

                            if (!empty($approvePayTypeCount)) {

                                if ($approvePayTypeCount == '00:00') {
                                    $approveCount = 0;
                                } else {
                                    $countArr = explode(':', $approvePayTypeCount);
                                    $otHoursFromMin = (int) $countArr[0] * 60;
                                    $otMin = (int) $countArr[1];
                                    $approveCount = $otHoursFromMin + $otMin;

                                }
                            }

                            $totalApprovedOtCount += $approveCount;
                            //update attendance summary pay type approved ot count
                            $updateAttendanceSummaryPayType = DB::table('attendanceSummaryPayTypeDetail')->where('payTypeId', $payType->id)->where('summaryId', $summaryId)->update(['approvedWorkTime' => $approveCount]);
                        }

                        //update attendance record wise total approved ot count
                        $updateTotalApproveOt = DB::table('postOtRequestDetail')->where('id', $detailRecord['id'])->update(['totalApprovedOt' => $totalApprovedOtCount, 'status' => $status]);

                    }
                }
                
                break;
            default:
                break;
        }

    }


    private function performFailierProcessForWorkflowRequest($workflowDefineData, $id, $workflowData, $currentPriorStateOfWfInstance) {
        switch ($workflowDefineData['contextId']) {
            case 1:
                // failure action for profile update request

                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first(); 
                $employeeId = $instanceResult->employeeId;
                break;
            case 2:
                // failure action for leave request
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $isReversUsedCount = false;

                // if (in_array($priorStateBeforeUpdate, $successStates)) {
                //     $isReversUsedCount = true;
                // }



                $instanceResult = $queryBuilder->first();
                $leaveData = json_decode($instanceResult->details, true);
                $leaveData  = (array) $leaveData; 
                $employeeId = $instanceResult->employeeId;
                $leaveId = $leaveData['id'];
                $leaveDates = [];

                $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
                    ->where('leaveRequestId', $leaveId)
                    ->get()->toArray();

                foreach ($leaveRequestDetails as $key => $value) {
                   $value = (array) $value;
                   if ($value['status'] == 'PENDING' || $value['status'] == 'APPROVED') {
                       $leaveDates[] = $value['leaveDate'];
                   }
                }

                //check whether requested dates related attendane summary records are locked
                $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $leaveDates);
                
                if ($hasLockedRecords) {
                    DB::rollback();
                    return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_CANNOT_CANCEL_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
                }

                $status = null;
                $coveringEmailType = null;
                // state id == 3 means its related to rejected state (according to the workflow intial configurations)
                if ($currentPriorStateOfWfInstance == 3) {
                    $status = 'REJECTED';
                    $coveringEmailType = 'leaveRejected';
                } else {
                    $status = 'CANCELED';
                    $coveringEmailType = 'leaveCanceled';
                }

                $workCalendarService = new WorkCalendarService ($this->store);
                $leaveTypeService = new LeaveTypeService ($this->store, $this->session);
                $leaveRequestService = new LeaveRequestService($this->store, $this->session, $this->fileStorage, $this, $this->redis, $workCalendarService, $leaveTypeService);
                $result = $leaveRequestService->reverseLeaveEntitlementAllocations($leaveData['id'], $status, $isReversUsedCount);
                if ($result['error']) {
                    DB::rollback();
                    return $this->error($result['statusCode'], $result['message'], $id);
                }
                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $leaveDates
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));

                //update leave request current state
                $leaveRequestModel = $this->getModel('leaveRequest', true);
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($leaveRequestModel, $oldDetail['id'], ['currentState' => $currentPriorStateOfWfInstance]);

                $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
                ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $leaveData['id'])
                ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                ->first();

                if (!is_null($coveringPersonRequestData)) {

                    $emailData = [
                        'fromDate' => $leaveData['fromDate'],
                        'toDate' => $leaveData['toDate'],
                        'numOfDays' => $leaveData['numberOfLeaveDates'],
                        'coveringEmployeeId' => $coveringPersonRequestData->coveringEmployeeId,
                        'employeeId' => $leaveData['employeeId']
                    ];

                    //send email to covering person when this particular leave is relate with covering person
                    $this->sendEmailForLeaveCoveringPerson($coveringEmailType, $emailData);
                }
                break;
            case 3:
                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $chageTimeData = json_decode($instanceResult->details, true);
                $employeeId = $instanceResult->employeeId;
                $chageTimeData = (array) $chageTimeData;
                $summaryId = $chageTimeData['summaryId'];
                $timeChangeDataset = [
                    "timeChangeId" =>  $chageTimeData['id'],
                    "type" => 1,
                    "shiftId" => 1,
                    "employeeId" => $employeeId,
                    "summaryId" => $summaryId
                ];

                $attendanceService = new AttendanceService($this->store, $this->session, $this);
                $result = $attendanceService->approveAttendanceTime($timeChangeDataset);
                if ($result['error']) {
                    DB::rollback();
                    return $this->error($result['statusCode'], $result['message'], $id);
                }
                break;
            case 4:
                //Failed Action For Apply Short Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $shortLeaveData = json_decode($instanceResult->details, true);
                $shortLeaveData  = (array) $shortLeaveData;
                $employeeId = $instanceResult->employeeId;

                $leaveDates = [];
                $leaveDates[] = $shortLeaveData['date'];

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $leaveDates
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));

                //update current state in shortLeaveRequest table
                $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                $leaveData['currentState'] = $currentPriorStateOfWfInstance;
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($shortLeaveRequestModel, $oldDetail['id'], $leaveData);
                
                break;
            case 5:
                //Failed Action For Shift change Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in shiftChangeRequest table
                $shiftChangeRequestModel = $this->getModel('shiftChangeRequest', true);
                $shiftChangeData['currentState'] = $currentPriorStateOfWfInstance;
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($shiftChangeRequestModel, $oldDetail['id'], $shiftChangeData);
                
                break;
            case 6:
                //Failed Action For Cancel Leave Workflow Context
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in cancelLeaveRequest table
                $cancelLeaveRequestModel = $this->getModel('cancelLeaveRequest', true);
                $cancelLeaveData['currentState'] = $currentPriorStateOfWfInstance;
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($cancelLeaveRequestModel, $oldDetail['id'], $cancelLeaveData);
                break;
            case 7:
                // failure action for profile update request

                $queryBuilder = DB::table('workflowInstance')
                ->select(
                    'workflowInstance.*',
                    'workflowDetail.details',
                    'workflowDetail.employeeId'
                )
                ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first(); 
                $employeeId = $instanceResult->employeeId;
                break;
            case 8:
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in cancelLeaveRequest table
                $cancelShortLeaveRequestModel = $this->getModel('cancelShortLeaveRequest', true);
                $cancelLeaveData['currentState'] = $currentPriorStateOfWfInstance;
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = $this->store->updateById($cancelShortLeaveRequestModel, $oldDetail['id'], $cancelLeaveData);
                
                break;
            case 9:
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in shortLeaveRequest table
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = DB::table('claimRequest')->where('id', $oldDetail['id'])->update(['currentState' => $currentPriorStateOfWfInstance]);

                //update claim allocation table
                $relatedAllocationRecord = (array) DB::table('claimAllocationDetail')
                                ->where('employeeId', $employeeId)
                                ->where('financialYearId', $oldDetail['financialYearId'])
                                ->where('claimTypeId', $oldDetail['claimTypeId'])->first();
                if (!empty($relatedAllocationRecord)) {
                    $pendingAmount = $relatedAllocationRecord['pendingAmount'] - $oldDetail['totalReceiptAmount'];
                    
                    $updateAllocationData = [
                        'pendingAmount' => $pendingAmount
                    ];
    
                    $relatedAllocationRecord = (array) DB::table('claimAllocationDetail')
                        ->where('id', $relatedAllocationRecord['id'])
                        ->update($updateAllocationData);
                }
                
                break;
            case 10:
                $queryBuilder = DB::table('workflowInstance')
                    ->select(
                        'workflowInstance.*',
                        'workflowDetail.details',
                        'workflowDetail.employeeId'
                    )
                    ->leftJoin('workflowDetail', 'workflowInstance.id', '=', 'workflowDetail.instanceId')
                    ->where([['workflowInstance.isDelete', '=', false], ['workflowInstance.id', '=', $id]]);

                $instanceResult = $queryBuilder->first();
                $employeeId = $instanceResult->employeeId;

                //update current state in shortLeaveRequest table
                $oldDetail = (array) json_decode($workflowData->details);
                $updateCurrentState = DB::table('postOtRequest')->where('id', $oldDetail['id'])->update(['currentState' => $currentPriorStateOfWfInstance]);

                $status = null;
                if ($currentPriorStateOfWfInstance == 3) {
                    $status = 'REJECTED';
                } else {
                    $status = 'CANCELED';
                }

                //update post ot detail table
                $relatedAllocationRecord = (array) DB::table('postOtRequestDetail')
                    ->where('postOtRequestId', $oldDetail['id'])
                    ->update(['status' => $status, 'totalApprovedOt' => 0]);
                break;
            default:
                break;
        }

    }

    private function performSuccessProcessForLeaveCancel($changeData, $workflowData)
    {
        $existingLeave = $this->store->getById($this->leaveRequestModel, $changeData['leaveRequestId']);
        if (is_null($existingLeave)) {
            DB::rollback();
            return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
        }
        $existingLeave = (array) $existingLeave;
        $employeeId = $existingLeave['employeeId'];
        $data['isInInitialState'] = false;


        $leaveRequestDetails = $this->store->getFacade()::table('leaveRequestDetail')
        ->where('leaveRequestId', $existingLeave['id'])
        ->whereIn('status', ['PENDING', 'APPROVED'])
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

        foreach ($changeData['cancelDatesDetails'] as $cancelKey => $cancelDateDetails) {
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

            }  elseif ($cancelDateDetails['isCheckedSecondHalf'] && !$cancelDateDetails['isCheckedFirstHalf']) {
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

        $workCalendarService = new WorkCalendarService ($this->store);
        $leaveTypeService = new LeaveTypeService ($this->store, $this->session);
        $leaveRequestService = new LeaveRequestService($this->store, $this->session, $this->fileStorage, $this, $this->redis, $workCalendarService, $leaveTypeService);
        $result =  $leaveRequestService->runLeaveCancellationProcess($existingLeave, $data, $isFullyCanceled, $cancelLeaveDates, $originalLeaveDatesIds, $newlyUpdatedLeaveDates, $changeData['id'], $leaveCancelRequestDetailIds, $leaveDates, $workflowData->currentApproveLevelSequence);
            

        $coveringPersonRequestData = DB::table('leaveCoveringPersonRequests')
            ->where('leaveCoveringPersonRequests.leaveRequestId', '=', $changeData['leaveRequestId'])
            ->where('leaveCoveringPersonRequests.isDelete', '=', false)
            ->first();
    
        if (!is_null($coveringPersonRequestData)) {    
            $emailData = [
                'fromDate' => $existingLeave['fromDate'],
                'toDate' => $existingLeave['toDate'],
                'numOfDays' => $existingLeave['numberOfLeaveDates'],
                'isFullyCanceled'=> $isFullyCanceled,
                'employeeId' =>$existingLeave['employeeId'],
                'coveringEmployeeId' => $coveringPersonRequestData->coveringEmployeeId
            ];
    
            //send email to covering person when this particular leave is relate with covering person
            $this->sendEmailForLeaveCoveringPerson('leaveCancelApproved', $emailData);
        }

    }

    private function performProfileUpdateWfSucessState($draftData, $currentEmployeeId) {
        $employeeService = new EmployeeService($this->store, $this->session, $this->redis, $this->fileStorage, $this, $this->modelService, $this->autoGenrateIdService, $this->activeDirectory, $this->azureUser);
        
        if ($draftData['isMultiRecord'] == false) {
            if ($draftData['relateAction'] == 'create') {
                //perform
            } elseif($draftData['relateAction'] == 'update') {
                unset($draftData['tabName']);
                unset($draftData['relateAction']);
                $res = $employeeService->updateEmployee($currentEmployeeId, $draftData);
            }
        } else {
            if ($draftData['relateAction'] == 'create') {
                $multirecordAttribute = $draftData['tabName'];
                unset($draftData['tabName']);
                unset($draftData['relateAction']);
                $res = $employeeService->createEmployeeMultiRecord($currentEmployeeId, $multirecordAttribute, $draftData, false);
                
            } elseif($draftData['relateAction'] == 'update') {
                $multirecordAttribute = $draftData['tabName'];
                $multirecordId = $draftData['id'];
                unset($draftData['tabName']);
                unset($draftData['relateAction']);
                $res = $employeeService->updateEmployeeMultiRecord($currentEmployeeId, $multirecordAttribute,$multirecordId, $draftData);
            } elseif($draftData['relateAction'] == 'delete') {
                $multirecordAttribute = $draftData['tabName'];
                $multirecordId = $draftData['id'];
                unset($draftData['tabName']);
                unset($draftData['relateAction']);
                $res = $employeeService->deleteEmployeeMultiRecord($currentEmployeeId, $multirecordAttribute,$multirecordId);
            }
            $this->updateEmployeeRecordUpdatedAtColumn($currentEmployeeId);
        }

        return $res;
    }

    private function getManagerId($employeeId)
    {
        $queryBuilder = $this->store->getFacade();

        $employeeCurrentJob = $queryBuilder::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->where('employeeId', $employeeId)->first(['reportsToEmployeeId']);

        return is_null($employeeCurrentJob) ? null : $employeeCurrentJob->reportsToEmployeeId;
    }

    private function hasPermittedToPerformAction($roleId, $workflowId, $performedActionId, $requestedEmployeeId)
    {
        $actionIds = [];

        $queryBuilder = $this->store->getFacade();

        // $workflowPermissions = $queryBuilder::table('workflowPermission')->where('roleId', $roleId)->where('isDelete', 0)->get('actionId');
        // foreach ($workflowPermissions as $workflowPermission) {
        //     $actionIds = array_merge($actionIds, json_decode($workflowPermission->actionId, true));
        // }

        // // if user can't performe action
        // if (!in_array($performedActionId, $actionIds)) {
        //     return false;
        // }


        // check requested user role has permissions for this action
        $userRolePermittedTransitions = $queryBuilder::table('workflowStateTransitions')->where('workflowId', $workflowId)
        ->where('actionId', $performedActionId)->where('isDelete', 0)->whereJsonContains('workflowStateTransitions.permittedRoles', (int)$roleId)->count();
        
        // check requested employee has direct permissions for this action
        $employeePermittedTransitions = $queryBuilder::table('workflowStateTransitions')->where('workflowId', $workflowId)
        ->where('actionId', $performedActionId)->where('isDelete', 0)->whereJsonContains('workflowStateTransitions.permittedEmployees', (int)$requestedEmployeeId)->count();;

        return ($employeePermittedTransitions > 0 || $userRolePermittedTransitions > 0);
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflow id
     * @param $Workflow array containing Workflow data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflow deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflow($id)
    {
        try {

            $dbWorkflow = $this->store->getById($this->workflowInstanceModel, $id);

            if (is_null($dbWorkflow)) {
                return $this->error(404, $id, null);
            }

            $this->store->getFacade()::table('workflowInstance')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a workflow.
     * 
     * @param $id workflow id
     * @param $Workflow array containing Workflow data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflow deleted successfully.",
     *      $data => null
     * 
     */
    public function hardDeleteWorkflow($id)
    {
        try {

            $dbWorkflow = $this->store->getById($this->workflowModel, $id);
            if (is_null($dbWorkflow)) {
                return $this->error(404, Lang::get('workflowMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $this->store->deleteById($this->workflowModel, $id);

            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_DELETE'), null);
        }
    }


    /**
     * Following function run workflow process for particular action.
     * 
     * @param $workflowDefineId workflow define id
     * @param $dataSet array containing action details data
     * @param $employeeId workflow related employee id
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflow created successfully.",
     *      $data => ['instanceId' => 1]
     * 
     */
    public function runWorkflowProcess($workflowDefineId, $dataSet, $employeeId)
    {
        try {
            $workflowDefineData = (array) $this->store->getById($this->workflowDefineModel, $workflowDefineId);
            if (empty($workflowDefineData)) {
                return $this->error(404, Lang::get('workflowMessages.basic.ERR_CREATE'), $workflowDefineId);
            }

            if (empty($workflowDefineData['numOfApprovalLevels'])) {
                return $this->error(404, Lang::get('workflowMessages.basic.ERR_CREATE'), $workflowDefineId); //TODO
            }

            // $insance = $queryBuilder->first();
            $instanceData['workflowId'] = $workflowDefineId;
            
            //each workflow initial state is pending the id related to pending state is 1
            $instanceData['currentStateId'] = 1;
            $instanceData['workflowEmployeeId'] = $employeeId;

            //each workflow initial approval level sequesnce must be 1
            $instanceData['currentApproveLevelSequence'] = 1;

            $saveInstance = (array) $this->store->insert($this->workflowInstanceModel, $instanceData, true);
            if (!$saveInstance) {
                return $this->error(500, Lang::get('workflowMessages.basic.ERR_CREATE'), []);
            }

            $workflowDetail['employeeId'] = $employeeId;
            $workflowDetail['instanceId'] = $saveInstance['id'];

            $workflowDetail['details'] = json_encode($dataSet);

            $result = $this->store->insert($this->workflowModel, $workflowDetail, true);
            if (!$result) {
                return $this->error(500, Lang::get('workflowMessages.basic.ERR_CREATE'), []);
            }

            // create relate approve level for workflow instance
            $approvalLevels= DB::table('workflowApprovalLevel')->select('*')
                ->where('workflowApprovalLevel.workflowId', '=', $workflowDefineId)
                ->where('workflowApprovalLevel.isDelete', '=', false)
                ->get();

            if (sizeof($approvalLevels) == 0) {
                return $this->error(500, Lang::get('workflowMessages.basic.ERR_CREATE'), []); //TODO
            }

            //set instance level record for request creation
            $approvalLevelDataForCreateRequest = [
                "workflowInstanceId" => $saveInstance['id'],
                "levelSequence" => 0,
                "levelStatus" => "APPROVED",
                "isLevelCompleted" => true,
                "isHaveNextLevel" => true
            ];
        
            $wfInstanceApprovalLevelIdForCreate = DB::table('workflowInstanceApprovalLevel')->insertGetId($approvalLevelDataForCreateRequest);

            $userData = $this->getEmployeeRelatedUserByEmployeeId($employeeId);
            $userData = (array) $userData;

            //set instance level Detail record for request creation
            $approvalLevelDetailForCreateRequest = [
                "workflowInstanceApproverLevelId" => $wfInstanceApprovalLevelIdForCreate,
                "performAction" => 1,
                "performBy" => $userData['id'],
                "performAt" => Carbon::now()->toDateTimeString(),
            ];

            $wfInstanceApprovalLevelDetailIdForCreate = DB::table('workflowInstanceApprovalLevelDetail')->insertGetId($approvalLevelDetailForCreateRequest);

            $levelPermissionDetails = [
                'levelPermittedEmployees' => [],
                'levelPermittedJobCategories' => [],
                'levelPermittedDesignations' => [],
                'levelPermittedUserRoles' => [],
                'levelPermittedCommonOptions' => [],
            ];

            foreach ($approvalLevels as $levelKey => $level) {
                $level = (array) $level;

                if($level['levelType'] == 'STATIC' && !empty($level['staticApproverEmployeeId'])) {
                    array_push($levelPermissionDetails['levelPermittedEmployees'], $level['staticApproverEmployeeId']);
                }
                if ($level['levelType'] == 'DYNAMIC') {
                    switch ($level['dynamicApprovalTypeCategory']) {
                        case 'COMMON':
                            if (!empty($level['commonApprovalType'])) {
                                array_push($levelPermissionDetails['levelPermittedCommonOptions'], $level['commonApprovalType']);
                            }
                            break;
                        case 'JOB_CATEGORY':
                            if (!empty($level['approverJobCategories'])) {
                                // array_push($levelPermissionDetails['levelPermittedJobCategories'], $level['approverJobCategories']);
                                $levelPermissionDetails['levelPermittedJobCategories'] = array_merge($levelPermissionDetails['levelPermittedJobCategories'],json_decode($level['approverJobCategories']));
                            }
                            break;
                        case 'DESIGNATION':
                            if (!empty($level['approverDesignation'])) {
                                // array_push($levelPermissionDetails['levelPermittedDesignations'], $level['approverDesignation']);
                                $levelPermissionDetails['levelPermittedDesignations'] = array_merge($levelPermissionDetails['levelPermittedDesignations'],json_decode($level['approverDesignation']));
                            }
                            break;
                        case 'USER_ROLE':
                            if (!empty($level['approverUserRoles'])) {
                                // array_push($levelPermissionDetails['levelPermittedUserRoles'], $level['approverUserRoles']);
                                $levelPermissionDetails['levelPermittedUserRoles'] = array_merge($levelPermissionDetails['levelPermittedUserRoles'],json_decode($level['approverUserRoles']));
                            }
                            break;
                        
                        default:
                            # code...
                            break;
                    }

                }

                if($level['levelType'] == 'POOL' && !empty($level['approverPoolId'])) {

                    
                    //get related Pool data
                    $approverPool= DB::table('workflowApproverPool')
                        ->where('workflowApproverPool.id', '=', $level['approverPoolId'])
                        ->where('workflowApproverPool.isDelete', '=', false)
                        ->first();

                    if (!is_null($approverPool)) {
                        $poolPermitedEmployees = json_decode($approverPool->poolPermittedEmployees);

                        if (!empty($poolPermitedEmployees)) {
                            $levelPermissionDetails['levelPermittedEmployees'] = array_merge($levelPermissionDetails['levelPermittedEmployees'], $poolPermitedEmployees);
                        }
                    }

                }

                $approvalLevelData = [
                    "workflowInstanceId" => $saveInstance['id'],
                    "levelSequence" => $level['levelSequence'],
                    "levelStatus" => "PENDING",
                    "isLevelCompleted" => false,
                    "isHaveNextLevel" => ($level['levelSequence'] ==  sizeof($approvalLevels)) ? false : true
                ];
                

                $wfInstanceApprovalLevelId = DB::table('workflowInstanceApprovalLevel')->insertGetId($approvalLevelData);
                if (!$wfInstanceApprovalLevelId) {
                    return $this->error(500, Lang::get('workflowMessages.basic.ERR_CREATE'), []); // TODO
                }
            }

            //update instance wise permitted approval details
            $updateInstanceData = [
                'levelPermittedEmployees' => (!empty($levelPermissionDetails['levelPermittedEmployees'])) ? json_encode(array_values(array_unique($levelPermissionDetails['levelPermittedEmployees']))) : json_encode([]),
                'levelPermittedJobCategories' => (!empty($levelPermissionDetails['levelPermittedJobCategories'])) ? json_encode(array_values(array_unique($levelPermissionDetails['levelPermittedJobCategories']))) : json_encode([]),
                'levelPermittedDesignations' => (!empty($levelPermissionDetails['levelPermittedDesignations'])) ? json_encode(array_values(array_unique($levelPermissionDetails['levelPermittedDesignations']))) : json_encode([]),
                'levelPermittedUserRoles' => (!empty($levelPermissionDetails['levelPermittedUserRoles'])) ? json_encode(array_values(array_unique($levelPermissionDetails['levelPermittedUserRoles']))) : json_encode([]),
                'levelPermittedCommonOptions' => (!empty($levelPermissionDetails['levelPermittedCommonOptions'])) ? json_encode(array_values(array_unique($levelPermissionDetails['levelPermittedCommonOptions']))) : json_encode([]),
            ];

            //update instance wise permitted approval data
            $updateInstance = (array) $this->store->updateById($this->workflowInstanceModel, $saveInstance['id'], $updateInstanceData);
            if (!$updateInstance) {
                return $this->error(500, Lang::get('workflowMessages.basic.ERR_CREATE'), []);
            }
            
            if ($workflowDefineData['contextId'] == 2) {
                $leaveRequestModel = $this->getModel('leaveRequest', true);
                $leaveData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($leaveRequestModel, $dataSet['id'], $leaveData);
            }

            if ($workflowDefineData['contextId'] == 4) {
                $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);
                $shortLeaveData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($shortLeaveRequestModel, $dataSet['id'], $shortLeaveData);
            }

            if ($workflowDefineData['contextId'] == 5) {
                $shiftChangeRequestModel = $this->getModel('shiftChangeRequest', true);
                $shiftChangeData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($shiftChangeRequestModel, $dataSet['id'], $shiftChangeData);
            }

            if ($workflowDefineData['contextId'] == 6) {
                $cancelLeaveRequestModel = $this->getModel('cancelLeaveRequest', true);
                $cancelLeaveData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($cancelLeaveRequestModel, $dataSet['id'], $cancelLeaveData);
            }
            if ($workflowDefineData['contextId'] == 8) {
                $cancelShortLeaveRequestModel = $this->getModel('cancelShortLeaveRequest', true);
                $cancelLeaveData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($cancelShortLeaveRequestModel, $dataSet['id'], $cancelLeaveData);
            }

            if ($workflowDefineData['contextId'] == 9) {
                $claimRequestRequestModel = $this->getModel('claimRequest', true);
                $claimRequestData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($claimRequestRequestModel, $dataSet['id'], $claimRequestData);
            }

            if ($workflowDefineData['contextId'] == 10) {
                $postOtRequestRequestModel = $this->getModel('postOtRequest', true);
                $postOtRequestData['currentState'] = 1;
                $updateCurrentState = $this->store->updateById($postOtRequestRequestModel, $dataSet['id'], $postOtRequestData);
            }
            
            $relatedCreateActionId = 1;
            $emailSend = $this->emailNotificationService->sendEmailNotificationsForRelatedPersons($relatedCreateActionId, $employeeId, $saveInstance['id'], $approvalLevelDetailForCreateRequest, $workflowDefineData['contextId']);
               
            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_CREATE'), ["instanceId" => $saveInstance['id']]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_CREATE'), null);
        }
    }

    private function getManagerPermittedActions($workflowId, $roleId)
    {
        $queryBuilder = $this->store->getFacade();
        // $actionIds = [];
        // // get permitted actions
        // $workflowPermissions = $queryBuilder::table('workflowPermission')->where('isDelete', 0)->where('roleId', $roleId)
        //     ->get('actionId');

        // foreach ($workflowPermissions as $workflowPermission) {
        //     $actionIds = array_merge($actionIds, json_decode($workflowPermission->actionId, true));
        // }

        $res = $queryBuilder::table('workflowStateTransitions')
            ->select('workflowAction.id')
            ->leftJoin('workflowAction', 'workflowStateTransitions.actionId', '=', 'workflowAction.id')
            ->where('workflowId', $workflowId)
            ->where('workflowStateTransitions.isDelete', 0)
            ->whereJsonContains('workflowStateTransitions.permittedRoles', (int)$roleId)
            // ->whereIn('workflowStateTransitions.actionId', $actionIds)
            ->get()->toArray();

        $actions = [];
        foreach ($res as $val) {
            $actions[] = $val->id;
        }
        return $actions;
    }

    private function getEmployeeRelatedUserByEmployeeId($employeeId)
    {
        $queryBuilder = $this->store->getFacade();

        $employeeRelateUser = $queryBuilder::table('employee')->select(
            'user.email',
            'user.id'
        )->leftJoin('user', 'user.employeeId', '=', 'employee.id')
            ->where('employeeId', $employeeId)->first(['email']);

        return is_null($employeeRelateUser) ? null : $employeeRelateUser;
    }

    public function filterRelatedWorkflow($contextId, $employeeId) 
    {

        //get default workflow
        $workFlowId = DB::table('workflowDefine')
            ->select('workflowDefine.id', 'workflowDefine.contextId')
            ->leftJoin('workflowContext', 'workflowDefine.contextId', '=', 'workflowContext.id')
            ->where('workflowContext.id', '=', $contextId)
            ->whereNull('workflowDefine.employeeGroupId')
            ->where('workflowDefine.isDelete', '=', false)
            ->orderBy('workflowDefine.createdAt', 'ASC')
            ->first();
                
        if (empty($workFlowId->id)) {
            return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_LINK_WF_EMPTY'), null);
        }
        $defaultWorkflowDefineId = $workFlowId->id;


        $workflowDefineId = null;
        //get all workflows that linked with employee group based on context id
        $employeeGroupRelateWorkflows = DB::table('workflowDefine')
            ->select('workflowDefine.id', 'workflowDefine.contextId', 'workflowDefine.employeeGroupId')
            ->leftJoin('workflowContext', 'workflowDefine.contextId', '=', 'workflowContext.id')
            ->where('workflowContext.id', '=', $contextId)
            ->whereNotNull('workflowDefine.employeeGroupId')
            ->where('workflowDefine.isDelete', '=', false)
            ->get();

        if (!empty($employeeGroupRelateWorkflows)) {

            foreach ($employeeGroupRelateWorkflows as $key => $workflow) {
                $workflow = (array) $workflow;
                $employees = $this->getEmployeeIdsByWorkflowGroupId($workflow['employeeGroupId']);
                
                if (!empty($employees) && in_array($employeeId, $employees)) {
                    $workflowDefineId = $workflow['id'];
                    break;
                }
            }

            if (empty($workflowDefineId)) {
                $workflowDefineId = $defaultWorkflowDefineId;
            }
        } else {
            $workflowDefineId = $defaultWorkflowDefineId;
        }

        return $workflowDefineId;
    }

    /* get Pending Request Count of leave , attendance and profile */
    public function getPendingRequestCount() {
       
        try {
            $permittedFields = ["*"];
            $user = $this->session->getUser();

            $pendingLeaveCount = 0;
            $pendingTimeChangeCount = 0;
            $pendingProfilChangeCount = 0;
            $pendingShiftChangeCount = 0;
            $pendingCancelLeaveCount = 0;
            $pendingResignationCount = 0;
            $pendingCancelShortLeaveCount = 0;
            $pendingClaimRequestCount = 0;
            $pendingPostOtRequestCount = 0;
            $pendingShortLeaveCount = 0;
            
            //to get leave request pending count
            $params = [
                "contextType" => 'all',
                "filters" => 'Pending'
            ];

            $contextWiseCount = $this->getPendingWorkflowCountsForTodoWidget(null ,$permittedFields, $params);

            foreach ($contextWiseCount as $key => $contextCountObj) {
                switch ($contextCountObj->contextId) {
                    case 1:
                        $pendingProfilChangeCount = $contextCountObj->total_count;
                        break;
                    case 2:
                        $pendingLeaveCount = $contextCountObj->total_count;
                        break;
                    case 3:
                        $pendingTimeChangeCount = $contextCountObj->total_count;
                        break;
                    case 4:
                        $pendingShortLeaveCount = $contextCountObj->total_count;
                        break;
                    case 5:
                        $pendingShiftChangeCount = $contextCountObj->total_count;
                        break;
                    case 6:
                        $pendingCancelLeaveCount = $contextCountObj->total_count;
                        break;
                    case 7:
                        $pendingResignationCount = $contextCountObj->total_count;
                        break;
                    case 8:
                        $pendingCancelShortLeaveCount = $contextCountObj->total_count;
                        break;
                    case 9:
                        $pendingClaimRequestCount = $contextCountObj->total_count;
                        break;
                    case 10:
                        $pendingPostOtRequestCount = $contextCountObj->total_count;
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }


            $coveringPersonRequestDataCount = 0;
            $employeeId = $this->session->getUser()->employeeId;

            if (!is_null($user->employeeRoleId)) {
                // to get leave covering person request count
                $coveringPersonRequestDataCount = DB::table('leaveCoveringPersonRequests')
                        ->leftJoin('employee','employee.id','=','leaveCoveringPersonRequests.coveringEmployeeId')
                        ->where('leaveCoveringPersonRequests.coveringEmployeeId', '=', $employeeId)
                        ->where('leaveCoveringPersonRequests.state', '=', 'PENDING')
                        ->where('leaveCoveringPersonRequests.isDelete', '=', false)
                        ->count();
            }

            // get resignation template instances
            $resignationTemplateInstances = DB::table('employeeJob')
                ->leftJoin('employeeJobformTemplateInstance', 'employeeJobformTemplateInstance.employeeJobId', '=', 'employeeJob.id')
                ->leftJoin('formTemplateInstance', 'formTemplateInstance.id', '=', 'employeeJobformTemplateInstance.formTemplateInstanceId')
                ->where('employeeJob.employeeId', '=', $employeeId)
                ->where('employeeJob.employeeJourneyType', '=', 'RESIGNATIONS')
                ->where('employeeJob.isRollback', '=', 0)
                ->whereNotIn('formTemplateInstance.status', ['COMPLETED', 'CANCELED'])
                ->get(['formTemplateInstance.hash']);

            // get confirmation template instances
            $confirmationTemplateInstances = DB::table('employeeJob')
                ->leftJoin('employeeJobformTemplateInstance', 'employeeJobformTemplateInstance.employeeJobId', '=', 'employeeJob.id')
                ->leftJoin('formTemplateInstance', 'formTemplateInstance.id', '=', 'employeeJobformTemplateInstance.formTemplateInstanceId')
                ->where('employeeJob.employeeId', '=', $employeeId)
                ->where('employeeJob.employeeJourneyType', '=', 'CONFIRMATION_CONTRACTS')
                ->where('employeeJob.isRollback', '=', 0)
                ->whereNotIn('formTemplateInstance.status', ['COMPLETED', 'CANCELED'])
                ->get(['formTemplateInstance.hash']);

            $pendingcount = [
                'leaveCount' => $pendingLeaveCount,
                'leaveCoveringPersonRequestsCount' => $coveringPersonRequestDataCount,
                'timeChangeCount' => $pendingTimeChangeCount,
                'profileCount' => $pendingProfilChangeCount,
                'shiftChangeRequestCount' => $pendingShiftChangeCount,
                'cancelLeaveRequestCount' => $pendingCancelLeaveCount,
                'resignationRequestCount' => $pendingResignationCount,
                'cancelShortLeaveRequestCount' => $pendingCancelShortLeaveCount,
                'claimRequestCount' => $pendingClaimRequestCount,
                'postOtRequestCount' => $pendingPostOtRequestCount,
                'shortLeaveCount' =>  $pendingShortLeaveCount,
                'resignationTemplateInstances' => $resignationTemplateInstances,
                'confirmationTemplateInstances' => $confirmationTemplateInstances
            ];
            
            return $this->success(200, Lang::get('workflowMessages.basic.SUCC_ALL_RETRIVE_PENDING_COUNT'), $pendingcount);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowMessages.basic.ERR_ALL_RETRIVE_PENDING_COUNT'), null);
        }
    }

    private function getLeaveRquestsThatManageCoveringPerosns($employeeId, $queryParams)
    {
        $leaveRequests = DB::table('leaveRequest')
        ->select('leaveRequest.*', 'leaveCoveringPersonRequests.state as coveringPersonState')
        ->leftJoin('leaveCoveringPersonRequests', 'leaveCoveringPersonRequests.leaveRequestId', '=', 'leaveRequest.id')
        ->where('leaveRequest.employeeId', $employeeId);

        if ($queryParams['filters'] == "All") {
            $leaveRequests =  $leaveRequests->whereIn('leaveCoveringPersonRequests.state', ['PENDING', 'DECLINED', 'CANCELED'])->get();
        } else if ($queryParams['filters'] == "Pending") {
            $leaveRequests =  $leaveRequests->whereIn('leaveCoveringPersonRequests.state', ['PENDING'])->get();
        } else if ($queryParams['filters'] == "Cancelled") {
            $leaveRequests =  $leaveRequests->whereIn('leaveCoveringPersonRequests.state', ['DECLINED', 'CANCELED'])->get();
        } else {
            $leaveRequests =  $leaveRequests->whereIn('leaveCoveringPersonRequests.state', ['NO_STATE'])->get();
        }

        //get Employee Details
        $employeeData = DB::table('employee')
        ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
        ->leftJoin('location', 'location.id', '=', 'employeeJob.locationId')
        ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
        ->leftJoin('division', 'division.id', '=', 'employeeJob.divisionId')
        ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
        ->where('employee.id', $employeeId)->first();

        $coveringPersonRelateLeaveSet = [];

        $contextId = 2;
        $selectedWorkflow = $this->filterRelatedWorkflow($contextId, $employeeId);
        if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
            return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
        }
                
        $workflowDefineId = $selectedWorkflow;

        $query = $leaveRequests->map(function ($object) use ($employeeData, $workflowDefineId, $employeeId) {
            $leaveRequestId = $object->id;
            $id = "cover-leave-".$object->id;
            $leaveData = $object;

            // Add the new property
            $object->details = json_encode($leaveData);
            $object->id = $id;
            // $object->actionId = json_encode([]);
            $object->actionName = null;
            $object->isCoveringPersonsRelateLeave = true;
            $object->actionPermittedEmployees = json_encode([]);
            $object->actionPermittedRoles = json_encode([]);
            $object->contextId = 2;
            $object->contextName = 'Apply Leave';
            $object->commentCount = 0;
            $object->createdAt = $object->createdAt;
            $object->createdBy = $object->createdBy;
            $object->employeeId = $employeeId;
            $object->firstName = $employeeData->firstName;
            $object->lastName = $employeeData->lastName;
            $object->isDelete = false;
            // $isInFinalFaliureState = false;
            $object->isInFinalSucessState = false;
            $object->isIn = false;
            $object->leaveRequestId = $leaveRequestId;
            // $object->postState =  ($object->coveringPersonState == 'PENDING') ? null : 1;
            // $object->postStateName = ($object->coveringPersonState  == 'PENDING' ) ? null : 'Pending';
            $object->currentStateId = ($object->coveringPersonState  == 'PENDING') ? 1 : 4;
            $object->priorStateName = ($object->coveringPersonState  == 'PENDING') ? 'Pending' : 'Canceled';
            $object->updatedAt = $object->updatedAt;
            $object->workflowId = $workflowDefineId;
            
            // Return the new object
            return $object;
        
        });

        return $query;
    }


     /**
     * Following function retrived workflow approval level wise states of workflow request.
     *
     * @param $id leave request id
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Comment Added Successfully"   
     *      $data => ['Level1' => ['state' => 'Pending']....]  
     */
    public function getApprovalLevelWiseStates($id)
    {
        try {

            $currentLevel = null;
            $successStateIncludeLevel = null;
            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $requestedUserId = $this->session->getUser()->id;
            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $sessionUserData = $this->session->getUser();

            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;

            //get related workflow instance
            $existingWorkflowInstance = DB::table('workflowInstance')
            ->select('workflowInstance.id as workflowInstanceId','workflowInstance.workflowId', 'workflowDetail.employeeId', 'workflowInstance.currentStateId', 'workflowInstance.currentApproveLevelSequence')
            ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
            ->leftJoin('workflowDefine', 'workflowDefine.id', '=', 'workflowInstance.workflowId')
            ->where('workflowInstance.id', $id)->first();

            if (!is_null($existingWorkflowInstance)) {
                $existingWorkflowInstance = (array) $existingWorkflowInstance;
            }

            $levelDetails = [];
            if ($existingWorkflowInstance['currentStateId'] == 4) {
                //check whether leave is cancel in intial level
                $intialLevelCancelData = DB::table('workflowInstanceApprovalLevel')
                ->where('workflowInstanceApprovalLevel.workflowInstanceId', $id)
                ->where('workflowInstanceApprovalLevel.levelStatus', 'CANCELED')
                ->where('workflowInstanceApprovalLevel.levelSequence', 0)->first();

                if (!is_null($intialLevelCancelData)) {
                    $levKey = 'Initial Level';
                     //get instance approval level details
                    $relatedWfApprovalLevelDetailData = DB::table('workflowInstanceApprovalLevelDetail')->where('workflowInstanceApproverLevelId', '=', $intialLevelCancelData->id)->where('performAction', 4)->first();
                
                    $relatedWfApprovalLevelDetailData = (array) $relatedWfApprovalLevelDetailData;

                    //get perform user
                    $performedUser = DB::table('user')
                        ->select(['id','firstName','lastName','employeeId'])
                        ->where('id', $relatedWfApprovalLevelDetailData['performBy'])
                        ->where('isDelete', false)->first();
                    if (empty($performedUser)) {
                        return [];
                    }

                    $performedUser = (array) $performedUser;

                    $performerName = $performedUser['firstName'].' '.$performedUser['lastName'];

                    //check whether employee has then get employee
                    if ($performedUser['employeeId']) {
                        $performedEmployee = DB::table('employee')
                            ->select(['id','firstName','lastName'])
                            ->where('id', $performedUser['employeeId'])
                            ->where('isDelete', false)->first();
                        
                        if (!is_null($performedEmployee)) {
                            $performedEmployee = (array) $performedEmployee;
                            $performerName = $performedEmployee['firstName'].' '.$performedEmployee['lastName'];
                        }
                    }

                    $levelDetails[$levKey]['comment'] = $relatedWfApprovalLevelDetailData['approverComment'];
                    $levelDetails[$levKey]['approvers'][] = $performerName;
                    $levelDetails[$levKey]['canAddComment'] = false;
                    $levelDetails[$levKey]['approversUserIds'] = [];
                    $levelDetails[$levKey]['isAfterCurrentLevel'] = false;
                    $levelDetails[$levKey]['isAfterSucessActionPerformLevel'] = false;
                    $levelDetails[$levKey]['isLevelPerform'] = false;
                    $levelDetails[$levKey]['state'] = 'Cancelled';
                    $levelDetails[$levKey]['stateTagColor'] = 'blue';
                    $levelDetails[$levKey]['performAt'] = $this->getFormattedDateForList($relatedWfApprovalLevelDetailData['performAt'], $timeZone);
                
                    return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET_LEVEL_WISE_STATES'), (array)$levelDetails);
                }
            }

            //get workflow inctance realted levels
            $existingWorkflowInstanceApprovalLevels = DB::table('workflowInstanceApprovalLevel')
            ->where('workflowInstanceApprovalLevel.workflowInstanceId', $id)
            ->where('workflowInstanceApprovalLevel.levelSequence', '!=', 0)->get();

            $workflowDefineData = (array) $this->store->getById($this->workflowDefineModel, $existingWorkflowInstance['workflowId']);
            if (is_null($workflowDefineData)) {
                return $this->error(404, Lang::get('workflowMessages.basic.ERR_UPDATE'), $id);
            }

            // get instance relate current approval level
            $instanceAprovalLevelData = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $id)->where('levelSequence', $existingWorkflowInstance['currentApproveLevelSequence'])->first();

            if (!is_null($instanceAprovalLevelData)) {
                $instanceAprovalLevelData = (array) $instanceAprovalLevelData;
            }

            //check whether loggedUser has permissions to perform in current level
            $permittedPerformActions = $this->getLevelPermissionsForPerformActions($instanceAprovalLevelData, $sessionUserData, $workflowDefineData, $requestedEmployeeId, $existingWorkflowInstance['employeeId']);


            
            if (!is_null($existingWorkflowInstanceApprovalLevels) && sizeof($existingWorkflowInstanceApprovalLevels) > 0) {
                foreach ($existingWorkflowInstanceApprovalLevels as $key => $level) {
                    $level = (array)$level;
                    $levelKey = 'Level ' . $level['levelSequence'];

                    switch ($level['levelStatus']) {
                        case 'PENDING':
                            $levelDetails[$levelKey]['state'] = 'Pending';
                            $levelDetails[$levelKey]['stateTagColor'] = 'orange';;
                            break;
                        case 'APPROVED':
                            $levelDetails[$levelKey]['state'] = 'Approved';
                            $levelDetails[$levelKey]['stateTagColor'] = 'green';;
                            break;
                        case 'CANCELED':
                            $levelDetails[$levelKey]['state'] = 'Canceled';
                            $levelDetails[$levelKey]['stateTagColor'] = 'blue';;

                            break;
                        case 'REJECTED':
                            $levelDetails[$levelKey]['state'] = 'Rejected';
                            $levelDetails[$levelKey]['stateTagColor'] = 'red';;

                            break;
                        
                        default:
                            # code...
                            break;
                    }

                    $levelDetails[$levelKey]['approvers'] = [];
                    $levelDetails[$levelKey]['approversUserIds'] = [];
                    $levelDetails[$levelKey]['comment'] = null;
                    $levelDetails[$levelKey]['canAddComment'] = false;
                    $levelDetails[$levelKey]['isAfterCurrentLevel'] = ($level['levelSequence'] > $existingWorkflowInstance['currentApproveLevelSequence']) ? true : false;
                    $levelDetails[$levelKey]['isAfterSucessActionPerformLevel'] = false;
                    $levelDetails[$levelKey]['isLevelPerform'] = false;


                    //get workflow related ApprovalLevel Cofigurations
                    $relatedWfApprovalLevelData = DB::table('workflowApprovalLevel')->where('workflowId', '=', $existingWorkflowInstance['workflowId'])->where('levelSequence', $level['levelSequence'])->first();
                    
                    if (empty($relatedWfApprovalLevelData)) {
                        return [];
                    }

                    $relatedWfApprovalLevelData = (array) $relatedWfApprovalLevelData;

                    if ($level['levelSequence'] == $existingWorkflowInstance['currentApproveLevelSequence']) {
                        if (sizeof($permittedPerformActions) > 0) {
                            $levelDetails[$levelKey]['canAddComment'] = true;
                        }
                    }

                    if ($level['levelStatus'] == 'PENDING') {
                        //get related approves
                        switch ($relatedWfApprovalLevelData['levelType']) {
                            case 'STATIC':
                                if (!empty($relatedWfApprovalLevelData['staticApproverEmployeeId'])) {
                                    $permittedEmployee = DB::table('employee')
                                    ->select(['id','firstName','lastName'])
                                    ->where('id', $relatedWfApprovalLevelData['staticApproverEmployeeId'])
                                    ->where('isDelete', false)->first();

                                    if (!is_null($permittedEmployee)) {
                                        $employeeName = $permittedEmployee->firstName.' '.$permittedEmployee->lastName;
                                        $levelDetails[$levelKey]['approvers'][] = $employeeName;
                                    }

                                }
                                
                                break;
                            case 'DYNAMIC':
                                
                                if (!empty($relatedWfApprovalLevelData['dynamicApprovalTypeCategory'])) {

                                    switch ($relatedWfApprovalLevelData['dynamicApprovalTypeCategory']) {
                                        case 'COMMON':
                                            if (!empty($relatedWfApprovalLevelData['commonApprovalType'])) {
                                                $permittedEmployeeId =  $this->getManagerId($existingWorkflowInstance['employeeId']);
                                                if (!is_null($permittedEmployeeId)) {
                                                    $permittedEmployee = DB::table('employee')
                                                        ->select(['id','firstName','lastName'])
                                                        ->where('id', $permittedEmployeeId)
                                                        ->where('isDelete', false)->first();

                                                    if (!is_null($permittedEmployee)) {
                                                        $employeeName = $permittedEmployee->firstName.' '.$permittedEmployee->lastName;
                                                        $levelDetails[$levelKey]['approvers'][] = $employeeName;
                                                    }
                                                }
                                            }
                                            break;
                                        case 'JOB_CATEGORY':
                                            if (!empty($relatedWfApprovalLevelData['approverJobCategories'])) {
                                                $jobCategories = json_decode($relatedWfApprovalLevelData['approverJobCategories']);
                                                //get related Job Categories
                                                $permittedJobCategories = DB::table('jobCategory')
                                                        ->select(['id','name'])
                                                        ->whereIn('jobCategory.id', $jobCategories)
                                                        ->where('isDelete', false)->get();
                                            
                                                if (!is_null($permittedJobCategories)) {
                                                    $approverJobCategories = '';
                                                    
                                                    foreach ($permittedJobCategories as $catkey => $category) {
                                                        $catNum = $catkey + 1;
                                                        error_log($category->name);
                                                        $approverJobCategories .= ($catNum < sizeof($permittedJobCategories)) ? ' '.$category->name.', ': ' '.$category->name;

                                                    }
    
                                                    $levelDetails[$levelKey]['approvers'][] = 'Job Category - ('.$approverJobCategories.')';
                                                }
                                            }
                                            break;
                                        case 'DESIGNATION':
                                            if (!empty($relatedWfApprovalLevelData['approverDesignation'])) {
                                                $jobTitles = json_decode($relatedWfApprovalLevelData['approverDesignation']);
                                                //get related Job Categories
                                                $permittedJobTitles = DB::table('jobTitle')
                                                        ->select(['id','name'])
                                                        ->whereIn('jobTitle.id', $jobTitles)
                                                        ->where('isDelete', false)->get();
                                            
                                                if (!is_null($permittedJobTitles)) {
                                                    $approverJobTitles = '';
                                                    
                                                    foreach ($permittedJobTitles as $titlekey => $title) {
                                                        $titleNum = $titlekey + 1;
                                                        $approverJobTitles .= ($titleNum < sizeof($permittedJobTitles)) ? ' '.$title->name.', ': ' '.$title->name;

                                                    }
    
                                                    $levelDetails[$levelKey]['approvers'][] = 'Designatoins - ('.$approverJobTitles.')';
                                                }
                                            }
                                            break;
                                        case 'USER_ROLE':
                                            if (!empty($relatedWfApprovalLevelData['approverUserRoles'])) {
                                                $userRoles = json_decode($relatedWfApprovalLevelData['approverUserRoles']);
                                                $roleArrData = $this->getPermittedRoleArr($userRoles, $existingWorkflowInstance['employeeId']);
                                                $approvers = array_unique(array_merge([], $roleArrData['empNameArr']));
                                                $levelDetails[$levelKey]['approvers'][] = $approvers;
                                                // $approverUserIds =  array_unique(array_merge($approverUserIds, $roleArrData['empIdsArr']));
                                                // $jobTitles = json_decode($relatedWfApprovalLevelData['approverUserRoles']);
                                                // //get related Job Categories
                                                // $permittedJobTitles = DB::table('jobTitle')
                                                //         ->select(['id','name'])
                                                //         ->whereIn('jobTitle.id', $jobTitles)
                                                //         ->where('isDelete', false)->get();
                                            
                                                // if (!is_null($permittedJobTitles)) {
                                                //     $approverJobTitles = '';
                                                    
                                                //     foreach ($permittedJobTitles as $titlekey => $title) {
                                                //         $titleNum = $titlekey + 1;
                                                //         $approverJobTitles .= ($titleNum < sizeof($permittedJobTitles)) ? ' '.$title->name.', ': ' '.$title->name;

                                                //     }
    
                                                //     $levelDetails[$levelKey]['approvers'][] = 'Designatoins - ('.$approverJobTitles.')';
                                                // }
                                            }
                                            break;
                                        
                                        default:
                                            # code...
                                            break;
                                    }
                                }
                                
                                break;
                            case 'POOL':
                                if (!empty($relatedWfApprovalLevelData['approverPoolId'])) {
                                    $permittedPool = DB::table('workflowApproverPool')
                                    ->select(['id','poolName'])
                                    ->where('id', $relatedWfApprovalLevelData['approverPoolId'])
                                    ->where('isDelete', false)->first();

                                    if (!is_null($permittedPool)) {
                                        $poolName = $permittedPool->poolName;
                                        $levelDetails[$levelKey]['approvers'][] = 'Approver Pool - ('.$poolName.')';
                                    }

                                }
                                
                                break;
                            
                            default:
                                # code...
                                break;
                        }
                    } else {
                        $hasOnePerformerForEachLevel = false;
                        if ($relatedWfApprovalLevelData['levelType'] == 'STATIC' || $relatedWfApprovalLevelData['levelType'] == 'DYNAMIC' || $relatedWfApprovalLevelData['levelType'] == 'POOL' ) {
                            $hasOnePerformerForEachLevel =true;
                        }

                        $stateIdSeq = null;

                        switch ($level['levelStatus']) {
                            case 'APPROVED':
                                $stateIdSeq = 2;
                                break;
                            case 'REJECTED':
                                $stateIdSeq = 3;
                                break;
                            case 'CANCELED':
                                $stateIdSeq = 4;
                                break;
                            
                            default:
                                # code...
                                break;
                        }

                        if ($hasOnePerformerForEachLevel) {

                            if (is_null($stateIdSeq)) {
                                //get instance approval level details
                                $relatedWfApprovalLevelDetailData = DB::table('workflowInstanceApprovalLevelDetail')->where('workflowInstanceApproverLevelId', '=', $level['id'])->first();
                            } else {
                                //get instance approval level details
                                $relatedWfApprovalLevelDetailData = DB::table('workflowInstanceApprovalLevelDetail')->where('workflowInstanceApproverLevelId', '=', $level['id'])->where('performAction', $stateIdSeq)->first();
                            }
                            
                            if (empty($relatedWfApprovalLevelDetailData)) {
                                return [];
                            }
                            $relatedWfApprovalLevelDetailData = (array) $relatedWfApprovalLevelDetailData;

                            //get perform user
                            $performedUser = DB::table('user')
                                ->select(['id','firstName','lastName','employeeId'])
                                ->where('id', $relatedWfApprovalLevelDetailData['performBy'])
                                ->where('isDelete', false)->first();
                            if (empty($performedUser)) {
                                return [];
                            }

                            $performedUser = (array) $performedUser;

                            $performerName = $performedUser['firstName'].' '.$performedUser['lastName'];

                            //check whether employee has then get employee
                            if ($performedUser['employeeId']) {
                                $performedEmployee = DB::table('employee')
                                    ->select(['id','firstName','lastName'])
                                    ->where('id', $performedUser['employeeId'])
                                    ->where('isDelete', false)->first();
                                
                                if (!is_null($performedEmployee)) {
                                    $performedEmployee = (array) $performedEmployee;
                                    $performerName = $performedEmployee['firstName'].' '.$performedEmployee['lastName'];
                                }
                            }

                            $levelDetails[$levelKey]['comment'] = $relatedWfApprovalLevelDetailData['approverComment'];
                            $levelDetails[$levelKey]['approvers'][] = $performerName;
                            $levelDetails[$levelKey]['performAt'] = $this->getFormattedDateForList($relatedWfApprovalLevelDetailData['performAt'], $timeZone);
                        }
                    }
                }
            }

            return $this->success(200, Lang::get('leaveRequestMessages.basic.SUCC_GET_LEVEL_WISE_STATES'), (array)$levelDetails);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveRequestMessages.basic.ERR_GET_LEVEL_WISE_STATES'), null);
        }
    }

    /**
     * Following function retrived workflow transitions permitted roles names as an array .
     *
     */
    private function getPermittedRoleArr($permittedRoles, $employeeId)
    {
        $permittedArray = [];
        $permittedEmpIds = [];

        //get relatedUser
        $userData = DB::table('user')->where('employeeId', $employeeId)->where('isDelete', false)->first();


        foreach ($permittedRoles as $key => $value) {
            switch ($value) {
                case 1:
                    $permittedArray[] = 'Admin Role';
                    $adminEmployeeIds = $this->getEmployeeIdsHasAdminRole();
                    $permittedEmpIds = (sizeof($permittedEmpIds) > 0) ? array_unique(array_merge($permittedEmpIds, $adminEmployeeIds)) : $adminEmployeeIds;
                    
                    break;
                case 3:
                    $permittedArray[] = 'Manager Role';
                    $adminEmployeeIds = $this->getEmployeeIdsHasManagerRole();
                    $permittedEmpIds = (sizeof($permittedEmpIds) > 0) ? array_unique(array_merge($permittedEmpIds, $adminEmployeeIds)) : $adminEmployeeIds;
                    break;

                default:
                    # code...
                    break;
            }
        }

        $dataSet = [
            'empNameArr' => $permittedArray,
            'empIdsArr' => $permittedEmpIds
        ];

        return $dataSet;
    }

    /**
     * Following function retrived workflow transitions permitted employee names as an array .
     *
     */
    private function getPermittedEmployeesArr($permittedEmployees)
    {
        $permittedArray = [];
        $permittedEmpidsArray = [];
        foreach ($permittedEmployees as $key => $value) {
            // $relatedEmployee = $this->store->getById($this->employeeModel, $value);
            $relatedEmployee = DB::table('employee')->select('employee.*','user.id as userId')->where('employee.id', $value)->leftJoin('user', 'user.employeeId', '=', 'employee.id')->where('employee.isDelete', false)->first();
            $permittedArray[] = $relatedEmployee->firstName . ' ' . $relatedEmployee->lastName;
            $permittedEmpidsArray[] = $relatedEmployee->userId;
        }

        $dataSet = [
            'empNameArr' => $permittedArray,
            'empIdsArr' => $permittedEmpidsArray
        ];

        return $dataSet;
    }

   
     /**
     * Following function retrived manager id of given employee
     *
     */
    private function getEmployeeIdsHasAdminRole()
    {
        $empIds = [];
        $queryBuilder = $this->store->getFacade();
        $adminRoleUsers = $queryBuilder::table('user')
        ->select('user.id')
        ->where('user.isDelete', false)
        ->whereNotNull('user.adminRoleId')->get();

        foreach ($adminRoleUsers as $key => $value) {
            $empIds[] = $value->id;
        }

        return $empIds;
    }

    /**
     * Following function retrived manager id of given employee
     *
     */
    private function getEmployeeIdsHasManagerRole()
    {
        $empIds = [];
        $queryBuilder = $this->store->getFacade();
        $adminRoleUsers = $queryBuilder::table('user')
        ->select('user.id')
        ->where('user.isDelete', false)
        ->whereNotNull('user.managerRoleId')->get();

        foreach ($adminRoleUsers as $key => $value) {
            $empIds[] = $value->id;
        }

        return $empIds;
    }

    /**
     * Following function retrived initial state of the workflow
     *
     */
    public function getInitialStateOfWorkflow($workflowDefineId)
    {
        $initialState = null;
        $queryBuilder = DB::table('workflowStateTransitions')
        ->select('workflowStateTransitions.id', 'workflowStateTransitions.workflowId', 'workflowStateTransitions.actionId', 'workflowStateTransitions.priorStateId', 'workflowStateTransitions.postStateId')
        ->whereNotIn(
            'workflowStateTransitions.priorStateId',
            function ($query) use ($workflowDefineId) {
                $query->select('workflowStateTransitions.postStateId')
                    ->from('workflowStateTransitions')
                    ->where('workflowStateTransitions.workflowId', '=', $workflowDefineId)
                    ->where('workflowStateTransitions.isDelete', '=', false)
                    ->get();
            }
        )
        ->where([['workflowStateTransitions.workflowId', '=', $workflowDefineId], ['workflowStateTransitions.isDelete', '=', false]])->first();

        if (!is_null($queryBuilder)) {
            $initialState = $queryBuilder->priorStateId;
        }

        return $initialState;
    }


    /**
     * Following function send email for leave covering person
     *
     */
    public function sendEmailForLeaveCoveringPerson ($emailType, $emailData)  
    {

        //get leave request employee detail
        $requestEmpData =  DB::table('employee')->select('workEmail','firstName','lastName')
        ->where('employee.id','=',$emailData['employeeId'])->first();

        //get covering person employee detail
        $coveringEmpData =  DB::table('employee')->select('workEmail','firstName', 'lastName')
        ->where('employee.id','=',$emailData['coveringEmployeeId'])->first();
        $emailBody  = null;
        $empName = $requestEmpData->firstName.' '.$requestEmpData->lastName;
        $dayString = $emailData['numOfDays'] == 1 ? 'Day' : 'Days';

        switch ($emailType) {
            case 'leaveApproved':
                $emailBody = "The Leave request of ".$empName." that applied from ".$emailData['fromDate']." to ".$emailData['toDate']." for ".$emailData['numOfDays'].$dayString."Which that you assigned as the covering person has been approved";
                break;
            case 'leaveRejected':
                $emailBody = "The Leave request of ".$empName." that applied from ".$emailData['fromDate']." to ".$emailData['toDate']." for ".$emailData['numOfDays'].$dayString."Which that you assigned as the covering person has been rejected";
                break;
            case 'leaveCanceled':
                $emailBody = "The Leave request of ".$empName." that applied from ".$emailData['fromDate']." to ".$emailData['toDate']." for ".$emailData['numOfDays'].$dayString."Which that you assigned as the covering person has been canceled";
                break;
            case 'leaveCancelApproved':
                if ($emailData['isFullyCanceled']) {
                    $emailBody = "The Leave request of ".$empName." that applied from ".$emailData['fromDate']." to ".$emailData['toDate']." for ".$emailData['numOfDays'].$dayString."Which that you assigned as the covering person has been canceled";
                } else {
                    $emailBody = "The Leave request of ".$empName." that applied from ".$emailData['fromDate']." to ".$emailData['toDate']." for ".$emailData['numOfDays'].$dayString."Which that you assigned as the covering person  has been changed";
                }
                break;
            default:
                # code...
                break;
        }

        //send email for covering person
        $newEmail =  dispatch(new EmailNotificationJob(new Email('emails.leaveCoveringPersonEmailContent', array($coveringEmpData->workEmail), "Leave Covering Request", array([]), array("receipientFirstName" => $coveringEmpData->firstName, "emailBody" => $emailBody))))->onQueue('email-queue');

    }

    private function getLevelPermissionsForPerformActions($instanceAprovalLevelData, $sessionUserData, $workflowData, $requestedEmployeeId, $employeeId) {

        //get workflow related ApprovalLevel Cofigurations
        $relatedWfApprovalLevelData = DB::table('workflowApprovalLevel')->where('workflowId', '=', $workflowData['id'])->where('levelSequence', $instanceAprovalLevelData['levelSequence'])->first();
        
        if (empty($relatedWfApprovalLevelData)) {
            return [];
        }
        $relatedWfApprovalLevelData = (array) $relatedWfApprovalLevelData;

        switch ($relatedWfApprovalLevelData['levelType']) {
            case 'STATIC':
                return (!empty($relatedWfApprovalLevelData['staticApproverEmployeeId']) && $relatedWfApprovalLevelData['staticApproverEmployeeId'] == $sessionUserData->employeeId) ? json_decode($relatedWfApprovalLevelData['approvalLevelActions']) : [];
                break;
            case 'DYNAMIC':

                if (!empty($relatedWfApprovalLevelData['dynamicApprovalTypeCategory'])) {
                    switch ($relatedWfApprovalLevelData['dynamicApprovalTypeCategory']) {
                        case 'COMMON':
                            if (!empty($relatedWfApprovalLevelData['commonApprovalType'])) {
                                if ($relatedWfApprovalLevelData['commonApprovalType'] == 'REPORTING_PERSON') {
                                    //get the reporting person of requester and check i
                                    return (!empty($sessionUserData->managerRoleId) && !is_null($sessionUserData->managerRoleId) && $requestedEmployeeId == $this->getManagerId($employeeId)) ?  json_decode($relatedWfApprovalLevelData['approvalLevelActions']) : [];
                                }
                            } else {
                                return [];
                            }
                            break;
                        case 'JOB_CATEGORY':
                            
                            $jobCategories = !empty($relatedWfApprovalLevelData['approverJobCategories']) ? json_decode($relatedWfApprovalLevelData['approverJobCategories']) : null;
                                
                            if (!is_null($jobCategories)) {
                                //get all employees that has manager role or admin role and that job categories
                                $dataSet = DB::table('employee')
                                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                                ->leftJoin('user', 'employee.id', '=', 'user.employeeId')
                                ->whereIn('employeeJob.jobCategoryId', $jobCategories)
                                ->where('employee.isDelete', false)
                                ->where(function($query) 
                                {
                                    $query->whereNotNull('user.managerRoleId');
                                    $query->orwhereNotNull('user.adminRoleId');

                                })->get(['employee.firstName','employee.id', 'user.managerRoleId', 'user.adminRoleId']);

                                $employeeIdArray = [];
                                foreach ($dataSet as $key => $employee) {
                                    $employee = (array) $employee;
                                    $employeeIdArray[] = $employee['id'];
                                }

                                return (in_array($requestedEmployeeId, $employeeIdArray)) ?  json_decode($relatedWfApprovalLevelData['approvalLevelActions']) : [];
                            } else {
                                return [];
                            }
                            break;
                        case 'DESIGNATION':
                            $designations = !empty($relatedWfApprovalLevelData['approverDesignation']) ? json_decode($relatedWfApprovalLevelData['approverDesignation']) : null;
                            if (!is_null($designations)) {
                                //get all employees that has manager role or admin role and that job categories
                                $dataSet = DB::table('employee')
                                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                                ->leftJoin('user', 'employee.id', '=', 'user.employeeId')
                                ->whereIn('employeeJob.jobTitleId', $designations)
                                ->where('employee.isDelete', false)
                                ->where(function($query) 
                                {
                                    $query->whereNotNull('user.managerRoleId');
                                    $query->orwhereNotNull('user.adminRoleId');

                                })->get(['employee.firstName','employee.id', 'user.managerRoleId', 'user.adminRoleId']);

                                $employeeIdArray = [];
                                foreach ($dataSet as $key => $employee) {
                                    $employee = (array) $employee;
                                    $employeeIdArray[] = $employee['id'];
                                }

                                return (in_array($requestedEmployeeId, $employeeIdArray)) ?  json_decode($relatedWfApprovalLevelData['approvalLevelActions']) : [];
                            } else {
                                return [];
                            }
                            break;
                        case 'USER_ROLE':
                            
                            $permittedUserRole = !empty($relatedWfApprovalLevelData['approverUserRoles']) ? json_decode($relatedWfApprovalLevelData['approverUserRoles']) : null;
                            if (!is_null($permittedUserRole)) {
                                // check is have admin role in the list
                                $hasAdminRole = in_array(1, $permittedUserRole) ? true : false;
                                $hasMangerRole = in_array(3, $permittedUserRole) ? true : false;

                                //get all employees that has manager role or admin role 
                                $dataSet = DB::table('user')
                                // ->leftJoin('user', 'employee.id', '=', 'user.employeeId')
                                ->where('user.isDelete', false)
                                ->where(function($query) use($hasAdminRole, $hasMangerRole)
                                {
                                    // if ($hasAdminRole && !$hasMangerRole) {
                                    //     $query->whereNotNull('user.adminRoleId');
                                    // }

                                    // // if ($hasAdminRole && $hasMangerRole) {
                                    // //     $query->whereNotNull('user.managerRoleId');
                                    // //     $query->orwhereNotNull('user.adminRoleId');
                                    // // }

                                    // // if (!$hasAdminRole && $hasMangerRole) {
                                    // //     $query->whereNotNull('user.managerRoleId');
                                    // // }

                                    if ($hasAdminRole && !$hasMangerRole) {
                                        $query->whereNotNull('user.adminRoleId');
                                    }

                                    if (!$hasAdminRole && $hasMangerRole) {
                                        $query->whereNotNull('user.managerRoleId');
                                    }
                                    
                                    if ($hasAdminRole && $hasMangerRole) {
                                        $query->whereNotNull('user.managerRoleId');
                                        $query->orwhereNotNull('user.adminRoleId');
                                    }


                                })
                                ->get(['user.firstName','user.id', 'user.managerRoleId', 'user.adminRoleId']);

                                $userIdArray = [];
                                foreach ($dataSet as $key => $user) {
                                    $user = (array) $user;
                                    $userIdArray[] = $user['id'];
                                }

                                return (in_array($sessionUserData->id, $userIdArray)) ?  json_decode($relatedWfApprovalLevelData['approvalLevelActions']) : [];
                            } else {
                                return [];
                            }
                            break;
                        
                        default:
                            # code...
                            break;
                    }
                } else {
                    return [];
                }

                break;
            case 'POOL':
                $employeeIdArray = [];
                if (!empty($relatedWfApprovalLevelData['approverPoolId']) ) {
                    $relatedWfAproverPoolData = DB::table('workflowApproverPool')->where('id', '=', $relatedWfApprovalLevelData['approverPoolId'])->first();

                    if (!is_null($relatedWfAproverPoolData)) {
                        $employeeIdArray = !empty($relatedWfAproverPoolData->poolPermittedEmployees) ? json_decode($relatedWfAproverPoolData->poolPermittedEmployees) : [];
                    }
                } 

                return (!empty($employeeIdArray) && in_array($requestedEmployeeId, $employeeIdArray)) ? json_decode($relatedWfApprovalLevelData['approvalLevelActions']) : [];

                break;
            
            default:
                # code...
                break;
        }

    }

    public function getWorkflowConfigTree ($workflowId)
    {
        try {

            $workFlowDefine = DB::table('workflowDefine')
                ->where('workflowDefine.id', '=', $workflowId)
                ->where('workflowDefine.isDelete', '=', false)
                ->first();

            //get related approval levels
            $workFlowLevels = DB::table('workflowApprovalLevel')
                ->where('workflowId', '=', $workflowId)
                ->where('isDelete', '=', false)
                ->get();



            $orgData = [
                'id' => 'n1',
                'nodeLevel' => 0,
                'name' => $workFlowDefine->workflowName,
                'isReadOnly' => $workFlowDefine->isReadOnly,
                'nodeType'=> 'mainNode',
                'parentEntityId' => null
            ];

            error_log(json_encode($workFlowDefine));


            if ($workFlowDefine->isProcedureDefined) {
                $orgData['children'] = [
                    [
                        'id' => 'n2',
                        'nodeLevel' => 1,
                        'name' => 'Workflow Process',
                        'isReadOnly' => $workFlowDefine->isReadOnly,
                        'nodeType' => 'workflowNode',
                        'parentEntityId' => 'n1',
                        'children' => []
                    ]
                ];
                
                if (!is_null($workFlowLevels) && !empty($workFlowLevels) && sizeof($workFlowLevels) > 0) {
                
                    $levelNodes = [];
                    $idSequence = 2;
                    $nodeLevel = 2;
                    $updatedNodeData = [];
                    foreach ($workFlowLevels as $levelKey => $workFlowLevel) {
                        $workFlowLevel = (array) $workFlowLevel;
                        $idSequence ++;
                        $nodeLevel ++;
                        //make level node
                        $tempLevelNode = [
                            'id' => 'n'.$idSequence,
                            'nodeLevel' => $nodeLevel,
                            'name' => $workFlowLevel['levelName'],
                            'isReadOnly' => $workFlowDefine->isReadOnly,
                            'nodeType' => 'approverLevelNode',
                            // 'approvalLevelId' => $workFlowLevel,
                            'levelSequence' => $workFlowLevel['levelSequence'],
                            'levelData' => json_encode($workFlowLevel),
                            'parentEntityId' => ($levelKey == 0) ? null : 'n'.($idSequence-2),
                        ];
                        $levelNode[] = $tempLevelNode;

                        $approvalLevelActions = json_decode($workFlowLevel['approvalLevelActions']);
                        if (!is_null($approvalLevelActions) && sizeof($approvalLevelActions) > 0) {
                            $idSequence ++;
                            $nodeLevel ++;
                            //add success node
                            $node1  = [
                                'id' => 'n'.$idSequence,
                                'nodeLevel' => $nodeLevel,
                                'name' => 'Approve',
                                'isReadOnly' => $workFlowDefine->isReadOnly,
                                'nodeType' => 'sucessActionNode',
                                // 'approvalLevelId' => $workFlowLevel,
                                'levelSequence' => $workFlowLevel['levelSequence'],
                                'parentEntityId' => 'n'.($idSequence-1),
                            ];
                            $levelNode[] = $node1;

                            
                            $idSequence ++;
                            $node2  = [
                                'id' => 'n'.$idSequence,
                                'nodeLevel' => $nodeLevel,
                                'name' => 'Reject',
                                'nodeType' => 'failierActionNode',
                                // 'approvalLevelId' => $workFlowLevel,
                                'levelSequence' => $workFlowLevel['levelSequence'],
                                'parentEntityId' => 'n'.($idSequence - 2),
                            ]; 
                            $levelNode[] = $node2;
                        }

                    }

                    //reverse node set
                    $reverseNodeList = array_reverse($levelNode);

                    $processedNodeSet = [];
                    $parentIds = [];

                    foreach ($reverseNodeList as $reverseNodeKey => $reverseNode) {
                        if ($reverseNode['parentEntityId']) {
                            $parentIds[] = $reverseNode['parentEntityId'];
                        }
                        $processedNodeSet[$reverseNode['id']] = $reverseNode;
                    }


                    $parentIds = array_values(array_unique($parentIds));
                    $finaleProcessedObj = null;

                    if (!empty($parentIds)) {
                        foreach ($parentIds as $parenteKey => $parentId) {
    
                            if (is_null($finaleProcessedObj)) {
                                $nodeData= $processedNodeSet[$parentId];
                            } else {
                                $nodeData = $processedNodeSet[$parentId]; 
                                $nodeData['children'][] = $finaleProcessedObj;
                            }
                            //remove relatedNode from list
                            unset($processedNodeSet[$parentId]);
    
                            //get related childrens
                           $childrenRes =  $this->getRelatedChildren($processedNodeSet, $parentId);
    
                           if (sizeof($childrenRes['childNodes']) > 0) {
                               $nodeData['children'] = (isset($nodeData['children']) && sizeof($nodeData['children']) > 0) ? array_merge($nodeData['children'],$childrenRes['childNodes']) : $childrenRes['childNodes'];
                           }
    
                           $processedNodeSet = $childrenRes['orgNodes'];
                           $finaleProcessedObj = $nodeData;
    
                        }
                    } else {
                        $finaleProcessedObj = $processedNodeSet['n'.$idSequence];
                        error_log(json_encode($idSequence));
                    }

                    
                    if (!is_null($finaleProcessedObj)) {
                        $orgData['children'][0]['children'][] = $finaleProcessedObj;
                        $orgData['children'][0]['children'][0]['parentEntityId'] = 'n2';
                    }

                }
            }

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET_DEP_ORG_CHART'), ['orgData' => $orgData, 'hierarchyConfig' => []]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GET_DEP_ORG_CHART'), null);
        }

    }

    private function getRelatedChildren($processedNodeSet, $parentId)
    {
        $childNodes = [];
        $orgNodes = $processedNodeSet;
        foreach ($processedNodeSet as $key => $node) {
            if ($node['parentEntityId'] == $parentId) {
                $childNodes[] = $node;
                unset($orgNodes[$key]);
            }
        }

        $res = [
            'childNodes' => (sizeof($childNodes) > 0) ?  array_reverse($childNodes) : [],
            'orgNodes' => $orgNodes
        ];
        return $res;

    }


    private function performSuccessProcessForShortLeaveLeaveCancel($changeData, $workflowData)
    {
        $existingShortLeave = $this->store->getById($this->shortLeaveRequestModel, $changeData['shortLeaveRequestId']);
        if (is_null($existingShortLeave)) {
            DB::rollback();
            return $this->error(404, Lang::get('leaveRequestMessages.basic.ERR_NOT_EXIST'), null);
        }
        $existingShortLeave = (array) $existingShortLeave;
        $leaveDates = [$existingShortLeave['date']];
        $employeeId = $existingShortLeave['employeeId'];
        $data['isInInitialState'] = false;
        $isFullyCanceled = true;

        $workCalendarService = new WorkCalendarService ($this->store);
        $leaveTypeService = new LeaveTypeService ($this->store, $this->session);
        $shortLeaveRequestService = new ShortLeaveRequestService($this->store, $this->session, $this->fileStorage, $this, $this->redis, $workCalendarService, $leaveTypeService);
        $result =  $shortLeaveRequestService ->runShortLeaveCancellationProcess($existingShortLeave, $isFullyCanceled,  $changeData['id'], $workflowData->currentApproveLevelSequence, $leaveDates);

    }
}
