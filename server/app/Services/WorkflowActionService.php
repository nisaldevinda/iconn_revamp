<?php

namespace App\Services;


use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\RoleType;
use App\Library\Session;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;

/**
 * Name: WorkflowActionService
 * Purpose: Performs tasks related to the WorkflowAction model.
 * Description: WorkflowAction Service class is called by the WorkflowActionController where the requests related
 * to WorkflowAction Model (basic operations and others). Table that is being modified is workflowAction.
 * Module Creator: Tharindu 
 */
class WorkflowActionService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $session;

    private $workflowActionModel;
    private $workflowInstanceModel;
    private $workflowModel;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;
        $this->workflowActionModel = $this->getModel('workflowAction', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->workflowModel = $this->getModel('workflowDefine', true);
    }
    

    /**
     * Following function creates a WorkflowAction.
     * 
     * @param $WorkflowAction array containing the WorkflowAction data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowAction => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowAction created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowAction($workflowAction)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->workflowActionModel, $workflowAction, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowActionMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowAction = $this->store->insert($this->workflowActionModel, $workflowAction, true);

            return $this->success(201, Lang::get('workflowActionMessages.basic.SUCC_CREATE'), $newWorkflowAction);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all workflowAction.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowAction created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getAllWorkflowAction($permittedFields, $options)
    {
        try {
            $filteredWorkflowAction = $this->store->getAll(
                $this->workflowActionModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );

            return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowAction);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /** 
     * Following function retrives all workflowAction relate with particular workflow context.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowAction created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getWorkflowContextBaseAction($contextId)
    {
        try {

            $queryBuilder = $this->store->getFacade();

            $filteredWorkflowAction =  $queryBuilder::table('workflowStateTransitions')
            ->select('workflowAction.*')
            ->leftJoin('workflowAction', 'workflowStateTransitions.actionId', '=', 'workflowAction.id')
            ->leftJoin('workflowDefine', 'workflowStateTransitions.workflowId', '=', 'workflowDefine.id')
            ->where('workflowDefine.contextId', $contextId)
                ->where('workflowStateTransitions.isDelete', 0)
                ->groupBy("workflowAction.id")
                ->get()->toArray();

            $id = null;
            switch ($contextId) {
                case 1:
                    //create action for profile update (id = 12)
                    $id = 12;
                    break;
                case 2:
                    //create action for leave apply  (id = 13)
                    $id = 13;
                    break;
                case 3:
                    //create action for time change request (id = 14)
                    $id = 14;
                    break;
                case 4:
                    //create action for short leave request (id = 15)
                    $id = 15;
                    break;
                case 5:
                    //create action for shift change request (id = 20)
                    $id = 20;
                    break;
                case 6:
                    //create action for cancel leave request (id = 24)
                    $id = 24;
                    break;
                case 7:
                    //create action for cancel leave request (id = 28)
                    $id = 28;
                    break;

                default:
                    $id = null;
                    break;
            }
            if (!empty($id)) {
                $defaultCreateAction = $queryBuilder::table('workflowAction')->where('id', $id)->first();

                if (!is_null($defaultCreateAction)) {
                    array_push($filteredWorkflowAction, $defaultCreateAction);
                }
            }

            return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowAction);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single WorkflowAction for a provided id.
     * 
     * @param $id workflowAction id
     * @return int | String | array
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, actionName": "Relative"}
     * ]
     */
    public function getWorkflowAction($id)
    {
        try {
            
           
            $workflowAction = $this->store->getById($this->workflowActionModel, $id);
            if (empty($workflowAction)) {
                return $this->error(404, Lang::get('workflowActionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_SINGLE_RETRIVE'), $workflowAction);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }





    /**
     * Following function updates a workflowAction.
     * 
     * @param $id workflowAction id
     * @param $WorkflowAction array containing WorkflowAction data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowAction updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowAction.
     * 
     */
    public function updateWorkflowAction($id, $workflowAction)
    {
        try {
               
           

            $validationResponse = ModelValidator::validate($this->workflowActionModel, $workflowAction, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowActionMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowAction = $this->store->getById($this->workflowActionModel, $id);
            if (is_null($dbWorkflowAction)) {
                return $this->error(404, Lang::get('workflowActionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->workflowActionModel, $id, $workflowAction);

            if (!$result) {
                return $this->error(502, Lang::get('workflowActionMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_UPDATE'), $workflowAction);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflowAction id
     * @param $WorkflowAction array containing WorkflowAction data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowAction deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflowAction($id)
    {
        try {            
            $dbWorkflowAction = $this->store->getById($this->workflowActionModel, $id);
            if (is_null($dbWorkflowAction)) {
                return $this->error(404, Lang::get('workflowActionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            // $queryBuilder = DB::table('workflowPermission')
            // ->select('workflowPermission.id','workflowPermission.actionId')
            // ->whereJsonContains('workflowPermission.actionId',(int)$id);
            // $existingActions = $queryBuilder->get();
            // if(count($existingActions)>0){
            //     foreach ($existingActions as $action) {
            //         $newArray=array_diff(json_decode($action->actionId,true), [$id]);
            //         $updatePermission = DB::table('workflowPermission')
            //         ->where('id', $action->id)
            //         ->update(['actionId' => json_encode(array_values($newArray))]);
            //     }
            // }

            $defaultActionIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
            
            // check whether this is system generated default action 
            if (in_array($id, $defaultActionIds)) {
                return $this->error(404, Lang::get('workflowActionMessages.basic.ERR_IS_DEFAULT_ACTION'), null);
            }

            //check whethe action is linked with state transition
            $linkedStateTransitions = $this->store->getFacade()::table('workflowStateTransitions')
                            ->where('actionId', (int)$id)
                            ->where('isDelete', false)->count();

            if ($linkedStateTransitions > 0) {
                return $this->error(404, Lang::get('workflowActionMessages.basic.ERR_HAS_LINKED_STATE_TRANSITION'), null);
            }


            $actionModelName = $this->workflowActionModel->getName();
            $result = $this->store->getFacade()::table($actionModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);
            return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_DELETE'), null);
        }
    }

    /** 
     * Following function retrives accessible workflowAction by requested scope.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowAction created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function accessibleWorkflowActions($workflowId, $employeeId, $scope, $instanceId)
    {
        try {

            
            $accessibleActions = [];
            $sessionUserData = $this->session->getUser();
            $requestedEmployeeId = $this->session->getUser()->employeeId;
            $scope = $scope;

            //check workflow instance realte leave and that leave has pending cancel leave requests
            $leaveRequestData = DB::table('leaveRequest')->where('leaveRequest.workflowInstanceId', '=', $instanceId)->first();
            if (!is_null($leaveRequestData)) {
                $leaveRequestData = (array)$leaveRequestData;

                //check this leave request has pending cancel leave request
                $hasPendingCancelLeaveRequests = $this->checkHasPendingCancelLeaveRequests($leaveRequestData);

                if ($hasPendingCancelLeaveRequests) {
                    return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_ALL_RETRIVE'), ['actions' => [], 'scope' => $scope]);
                }
            }

            
            //get related workflowInstance Record
            $workflowInstanceData = $this->store->getById($this->workflowInstanceModel, $instanceId);
            
            if (is_null($workflowInstanceData)) {
                return $this->error(404, $instanceId, null);
            }
            
            $workflowInstanceData = (array) $workflowInstanceData;
            
            $workflowData = $this->store->getById($this->workflowModel, $workflowId);
            if (is_null($workflowData)) {
                return $this->error(404, $workflowId, null);
            }
            $workflowData = (array) $workflowData;
        
            // if own request treat as employee
            if ($requestedEmployeeId == $employeeId) {
                if ($workflowData['isAllowToCancelRequestByRequester']) {
                    //state id == 1 mean its in the pending state
                    if ($workflowInstanceData['currentApproveLevelSequence'] == 1 && $workflowInstanceData['currentStateId'] == 1) {
                        // get instance relate approval level
                        $instanceAprovalLevel = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $instanceId)->where('levelSequence', $workflowInstanceData['currentApproveLevelSequence'])->first();
    
                        if (!empty($instanceAprovalLevel)) {
                            //check whether any one perform a action for this level of the request
                            $leaveRequestDataActionPeroformDetailCount = DB::table('workflowInstanceApprovalLevelDetail')->where('workflowInstanceApproverLevelId', '=', $instanceAprovalLevel->id)->count();
    
                            if ($leaveRequestDataActionPeroformDetailCount == 0) {
                                $cancelActionData = DB::table('workflowAction')->where('id', '=', 4)->get();
    
                                
                                $cancelActionData[0]->isPrimary = true;
                                $accessibleActions = $cancelActionData;
                            }
                        }
                    }
                }
                $scope = RoleType::EMPLOYEE;
            } else {

                // get instance relate approval level
                $instanceAprovalLevelData = DB::table('workflowInstanceApprovalLevel')->where('workflowInstanceId', '=', $instanceId)->where('levelSequence', $workflowInstanceData['currentApproveLevelSequence'])->first();
                $instanceAprovalLevelData = (array) $instanceAprovalLevelData;

                //state id == 1 mean its in the pending state (if current state is not equal to pending can not to any actions)
                if ($workflowInstanceData['currentStateId'] == 1) {
                    if ($instanceAprovalLevelData['levelStatus'] == 'PENDING' && !$instanceAprovalLevelData['isLevelCompleted']) {
                        //check whether loggedUser has permissions to perform actions
                        $permittedPerformActions = $this->getLevelPermissionsForPerformActions($instanceAprovalLevelData, $sessionUserData, $workflowData, $requestedEmployeeId, $employeeId);
                        if (sizeof($permittedPerformActions) > 0) {
                            $permittedActionData = DB::table('workflowAction')->whereIn('id', $permittedPerformActions)->get();
                            $permittedActionData[0]->isPrimary = true;
                            $accessibleActions = $permittedActionData;
                        }
    
                    }
                }


                if (!empty($this->session->getUser()->managerRoleId) && $this->session->getUser()->adminRoleId) {
                    $scope = RoleType::ADMIN;
                } elseif (empty($this->session->getUser()->managerRoleId) && !$this->session->getUser()->adminRoleId) {
                    $scope = RoleType::MANAGER;
                } elseif (empty($this->session->getUser()->managerRoleId) && $this->session->getUser()->adminRoleId) {
                    $scope = RoleType::ADMIN;
                }
            }
            
            return $this->success(200, Lang::get('workflowActionMessages.basic.SUCC_ALL_RETRIVE'), ['actions' => $accessibleActions, 'scope' => $scope]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowActionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
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
                                ->where('user.isDelete', false)
                                ->where(function($query) use($hasAdminRole, $hasMangerRole)
                                {
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

    /** 
     * Following function check leave request has pending cancel leave requests
     * 
     * @return boolean
     * 
     */
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


    private function getManagerId($employeeId)
    {
        $queryBuilder = $this->store->getFacade();

        $employeeCurrentJob = $queryBuilder::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->where('employeeId', $employeeId)->first(['reportsToEmployeeId']);

        return is_null($employeeCurrentJob) ? null : $employeeCurrentJob->reportsToEmployeeId;
    }
}