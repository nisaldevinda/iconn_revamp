<?php

namespace App\Services;

use Log;
use App\Exceptions\Exception;
use App\Services\EmployeeService;
use App\Library\ModelValidator;
use App\Library\Session;
use App\Library\Redis;
use App\Library\Store;
use App\Library\Email;
use App\Jobs\EmailNotificationJob;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;
use App\Library\FileStore;
use App\Traits\SessionHelper;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
/**
 * Name: EmailNotificationService
 * Purpose: Performs tasks related to the email notificatons model.
 * Module Creator: Tharindu Darshana
 */
class EmailNotificationService extends BaseService
{
    use JsonModelReader;
    use SessionHelper;

    private $store;
    private $workflowModel;
    private $userModel;
    private $userRoleModel;
    private $departmentModel;
    private $locationModel;
    private $workflowDefineModel;
    private $workflowInstanceModel;
    private $employeeModel;
    private $session;
    private $employeeService;
    private $leaveRequestDetailModel;
    private $fileStorage;
    private $redis;
    private $modelService;


    public function __construct(Store $store, Session $session, FileStore $fileStorage, Redis $redis, ModelService $modelService)
    {
        $this->store = $store;
        $this->workflowModel = $this->getModel('workflowDetail', true);
        $this->userModel = $this->getModel('user', true);
        $this->userRoleModel = $this->getModel('userRole', true);
        $this->departmentModel =  $this->getModel('department', true);
        $this->locationModel =  $this->getModel('location', true);
        $this->workflowDefineModel = $this->getModel('workflowDefine', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->leaveRequestDetailModel= $this->getModel('leaveRequestDetail', true);
        $this->session = $session;
        $this->redis = $redis;
        $this->employeeModel = $this->getModel('employee', true);
        $this->fileStorage = $fileStorage;
        $this->modelService = $modelService;
    }
    

    public function sendEmailNotificationsForRelatedPersons($relatedActionId, $employeeId, $instanceId = null, $actionPerformerDetails = [], $contextId) {

        try {

            $queryBuilder = DB::table('emailTemplate')
            ->select('*')
            ->where([['emailTemplate.isDelete', '=', false], ['emailTemplate.formName', '=', 'workflow'], ['emailTemplate.actionId', '=', $relatedActionId], ['emailTemplate.status', '=', true], ['emailTemplate.workflowContextId', '=', $contextId]]);
        
            $relatedEmailTemplates = $queryBuilder->get();

            if (sizeof($relatedEmailTemplates) > 0) {
                foreach ($relatedEmailTemplates as $key => $value) {
                    $toEmailAddress = [];
                    $ccEmailAddress = [];
                    $bccEmailAddress = [];

                    $value = (array) $value;
                    $toArray = (!empty($value['to'])) ? json_decode($value['to']) : [];
                    $ccArray = (!empty($value['cc'])) ? json_decode($value['cc']) : [];
                    $bccArray = (!empty($value['bcc'])) ? json_decode($value['bcc']) : [];

                    if (!empty($toArray)) {
                        $toEmailAddress = $this->getRelatedEmailAddressList($toArray, $employeeId, $value, $instanceId, $actionPerformerDetails);
                    }

                    if (!empty($ccArray)) {
                        $ccEmailAddress = $this->getRelatedEmailAddressList($ccArray, $employeeId, null, null, $actionPerformerDetails);
                        $ccEmailAddress = array_values($ccEmailAddress);
                    }

                    if (!empty($bccArray)) {
                        $bccEmailAddress = $this->getRelatedEmailAddressList($bccArray, $employeeId, null, null, $actionPerformerDetails);
                        $bccEmailAddress = array_values($bccEmailAddress);
                    }
                    
                    $ContentData = $this->generateDocumentContent($value['contentId'], $employeeId, $relatedActionId, $instanceId, $value['workflowContextId'], $actionPerformerDetails);
                    
                    foreach ($toEmailAddress as $idKey => $val) {
                        
                        $strArr = str_split($idKey);
                        
                        $explodeArr = explode($strArr[0],$idKey);
                        $userId = $explodeArr[1];

                        $content = $this->renderRecipientDetails($ContentData['data']['content'],$userId);
                        
                        $tempArr = [];
                        $tempArr[] = $val;
                        $sendEmail =  dispatch(new EmailNotificationJob(new Email('emails.emailNotificationContent', $tempArr, $value['subject'],  $ccEmailAddress, ['emailBody'=>$content])))->onQueue('email-queue');
                    }

                }
            }

            return $this->success(200, Lang::get('emailNotificationMessages.basic.SUCC_SEND'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            error_log(json_encode($e));
            return $this->error($e->getCode(), Lang::get('emailNotificationMessages.basic.ERR_SEND'), null);
        }
    }

    private function getRelatedEmailAddressList($receipentArray, $employeeId, $employeeTemplateData = NULL, $instanceId = null, $actionPerformerDetails = []) {
        
        $emailArr = [];

        foreach ($receipentArray as $key => $value) {
            switch ($value) {
                case 'employee':
                    $userData = $this->getEmployeeRelatedUserByEmployeeId($employeeId);

                    if (!is_null($userData)) {
                        $userData = (array)$userData;
                        $idKey = 'u'.$userData['userId'];
                        $emailArr[$idKey] = $userData['email'];
                    }

                    break;
                case 'manager':
                    $managerId = $this->getManagerId($employeeId);
                   
                    if (!is_null($managerId)) {
            
                        $userData = $this->getEmployeeRelatedUserByEmployeeId($managerId);

                        if (!is_null($userData)) {
                            $userData = (array)$userData;
                            $idKey = 'u'.$userData['userId'];
                            $emailArr[$idKey] = $userData['email'];
                        }
                    }
                    break;
                case 'allUsers':

                    $permittedFields = ["email"];
                    $options = [
                        "sorter" => null,
                        "pageSize" => null,
                        "current" => null,
                        "filter" => null,
                        "keyword" => null,
                        "searchFields" => ['email', 'fullName'],
                    ];
                    $users = $this->store->getAll(
                        $this->userModel,
                        $permittedFields,
                        $options,
                        [],
                        [['id', '!=', 2]] // hide default system admin
                    );

                    if (sizeof($users) > 0) {
                        foreach ($users as $key => $value) {
                            $value = (array) $value;
                            $idKey = 'u'.$value['id'];

                           $emailArr[$idKey] = $value['email'];
                        }
                    }
                    break;
                case 'allRoles':
                    $roleRelateEmails =  $this->getUserRoleRelatedUserEmails('all');
                    $emailArr = array_merge($emailArr,  $roleRelateEmails);
                    break;
                case 'allDepartments':
                    $departmentRelateEmails =  $this->getDepartmentRelatedUserEmails('all');
                    $emailArr = array_merge($emailArr,  $departmentRelateEmails);
                    break;
                case 'allLocations':
                    $locationRelateEmployeeEmails =  $this->getLocationRelatedUserEmails('all');
                    $emailArr = array_merge($emailArr,  $locationRelateEmployeeEmails);
                    break;
                case 'nextActionPerformer':
                    if (!empty($instanceId)) {
                        $nextPerformActions = (!empty($employeeTemplateData['nextPerformActions'])) ? json_decode($employeeTemplateData['nextPerformActions']) : [];
                        $nextActionPerformerEmployeeEmails =  $this->getNextActionPerformerBaseUserEmails($nextPerformActions, $instanceId);
                        $emailArr = array_merge($emailArr,  $nextActionPerformerEmployeeEmails);
                    }
                    break;
                case 'nextLevelPerformer':
                    $queryBuilder = $this->store->getFacade();
                    if (!empty($instanceId) && !empty($actionPerformerDetails)) {
                        // get current instance level data
                        $currentInstanceApprovalLevelData = $queryBuilder::table('workflowInstanceApprovalLevel')->where('id', $actionPerformerDetails['workflowInstanceApproverLevelId'])->first();
                        if (!is_null($currentInstanceApprovalLevelData)) {
                            $currentInstanceApprovalLevelData = (array)$currentInstanceApprovalLevelData;

                            if ($currentInstanceApprovalLevelData['isHaveNextLevel']) {
                                $nextLevelSequence = $currentInstanceApprovalLevelData['levelSequence'] + 1;

                                //get next instance approval level data 
                                $nextInstanceApprovalLevelData = $queryBuilder::table('workflowInstanceApprovalLevel')->where('levelSequence', $nextLevelSequence)->where('workflowInstanceId', $instanceId)->first();
                                $nextInstanceApprovalLevelData = (array) $nextInstanceApprovalLevelData;
                                $nextLevelPerformerUserEmails =  $this->getLevelWisePerformerBaseUserEmails($nextInstanceApprovalLevelData, $instanceId);

                                $emailArr = array_merge($emailArr, $nextLevelPerformerUserEmails);

                            }
                        }
                    }
                    break;
                case 'currentLevelPerformer':
                    $queryBuilder = $this->store->getFacade();
                    if (!empty($instanceId) && !empty($actionPerformerDetails)) {
                        // get current instance level data
                        $currentInstanceApprovalLevelData = $queryBuilder::table('workflowInstanceApprovalLevel')->where('id', $actionPerformerDetails['workflowInstanceApproverLevelId'])->first();
                        if (!is_null($currentInstanceApprovalLevelData)) {
                            $currentInstanceApprovalLevelData = (array)$currentInstanceApprovalLevelData;

                            $currentLevelPerformerUserEmails =  $this->getLevelWisePerformerBaseUserEmails($currentInstanceApprovalLevelData, $instanceId);

                            $emailArr = array_merge($emailArr, $currentLevelPerformerUserEmails);

                        }
                    }
                    break;
                default:
                    $data = $this->processSelectedIds($value);

                    switch ($data['section']) {
                        case 'user':
                            $userData = $this->store->getById($this->userModel, $data['id']);
                            if (empty($userData)) {
                                return $this->error(404, Lang::get('userMessages.basic.ERR_USER_EXIST'), $data['id']);
                            }
                            $userData = (array) $userData;

                            if ($userData['email']) {
                                $idKey = 'u'.$userData['id'];
                                $emailArr[$idKey] = $userData['email'];
                            }
                            break;
                        case 'role':
                            $roleRelateEmails =  $this->getUserRoleRelatedUserEmails($data['id']);
                            $emailArr = array_merge($emailArr,  $roleRelateEmails);
                            break;
                        case 'department':
                            $departmentRelateEmails =  $this->getDepartmentRelatedUserEmails($data['id']);
                            $emailArr = array_merge($emailArr,  $departmentRelateEmails);
                            break;
                        case 'location':
                            $locationRelateEmployeeEmails =  $this->getLocationRelatedUserEmails($data['id']);
                            $emailArr = array_merge($emailArr,  $locationRelateEmployeeEmails);
                            break;
                        
                        default:
                            # code...
                            break;
                    }
                    break;
            }

        }
        return $emailArr;

    }

    private function getUserRoleRelatedUserEmails ($roleId) {
        $queryBuilder = $this->store->getFacade();
        $emails = [];
        if ($roleId == 'all') {
            $permittedFields = ["id","type"];
            $options = [
                "sorter" => null,
                "pageSize" => null,
                "current" => null,
                "filter" => null,
                "keyword" => null,
                "searchFields" => ['title', 'type']
            ];

            $userRoles = $this->store->getAll(
                $this->userRoleModel,
                $permittedFields,
                $options,
                []
            );
        } else {
            $userRoleData = $this->store->getById($this->userRoleModel, $roleId);
            if (empty($userRoleData)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_NONEXISTENT_USER_ROLE'), $managerId);
            }
            $userRoleData = (array)$userRoleData;
            $userRoles[] = [
                'id' =>  $userRoleData['id'],
                'type' => $userRoleData['type']
            ];
        }


        if (sizeof($userRoles) > 0) {
            foreach ($userRoles as $key => $role) {
                $role = (array) $role;
                    
                if ($role['type'] == 'ADMIN') { 
                    $whereQuery = " WHERE user.adminRoleId = " .  $role['id'];
                }

                if ($role['type'] == 'EMPLOYEE') { 
                    $whereQuery = " WHERE user.employeeRoleId = " .  $role['id'];
                }

                if ($role['type'] == 'MANAGER') { 
                    $whereQuery = " WHERE user.managerRoleId = " .  $role['id'];
                }


                $query = "SELECT  user.email, user.id as userId FROM user {$whereQuery}; ";
                $relateUsers = DB::select($query);
            
                if (sizeof($relateUsers) > 0) {
                    foreach ($relateUsers as $key1 => $user) {
                        $user = (array) $user;
                        $idKey = 'u'.$user['userId'];
                        $emails[$idKey] = $user['email'];
                    }
                }
            }
        }

        return $emails;
    }

    private function getDepartmentRelatedUserEmails ($departmentId) {
        $queryBuilder = $this->store->getFacade();
        $emails = [];
        if ($departmentId == 'all') {
            $permittedFields = ["id"];
            $options = [
                "sorter" => null,
                "pageSize" => null,
                "current" => null,
                "filter" => null,
                "keyword" => null,
                "searchFields" => ['name']
            ];

            $departments = $this->store->getAll(
                $this->departmentModel,
                $permittedFields,
                $options,
                []
            );
        } else {
            $userRoleData = $this->store->getById($this->departmentModel, $departmentId);
            if (empty($userRoleData)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NOT_EXIST'), $managerId);
            }
            $departments[] = [
                'id' =>  $departmentId
            ];
        }

        if (sizeof($departments) > 0) {
            foreach ($departments as $key => $department) {
                $department = (array) $department;
                $relateUsers = $this->getDepartmentRealtedEmployees($department['id']);

                if (sizeof($relateUsers) > 0) {
                    foreach ($relateUsers as $key1 => $user) {
                        $user = (array) $user;

                        $userData = $this->getEmployeeRelatedUserByEmployeeId($user['id']);

                        if (!is_null($userData)) {
                            $userData = (array)$userData;
                            $idkey = 'u'.$userData['userId'];
                            $emails[$idkey] = $userData['email'];
                        }
                        // $emails[] = $user['workEmail'];
                    }
                }
            }
        }

        return $emails;
    }
    
    private function getDepartmentRealtedEmployees($departmentId)
    {
        $queryBuilder = $this->store->getFacade();
        
        $employeeCurrentJob = $queryBuilder::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
        ->where('employeeJob.departmentId', $departmentId)->get();
        
        return is_null($employeeCurrentJob) ? null : $employeeCurrentJob;
    }

    
    private function getLocationRelatedUserEmails ($locationId) {
        $queryBuilder = $this->store->getFacade();
        $emails = [];
        if ($locationId == 'all') {
            $permittedFields = ["id"];
            $options = [
                "sorter" => null,
                "pageSize" => null,
                "current" => null,
                "filter" => null,
                "keyword" => null,
                "searchFields" => ['name']
            ];

            $locations = $this->store->getAll(
                $this->locationModel,
                $permittedFields,
                $options,
                []
            );
        } else {
            $userRoleData = $this->store->getById($this->locationModel, $locationId);
            if (empty($userRoleData)) {
                return $this->error(404, Lang::get('locationMessages.basic.ERR_NOT_EXIST'), $locationId);
            }
            $userRoleData = (array)$userRoleData;
            $locations[] = [
                'id' =>  $locationId
            ];
        }

        if (sizeof($locations) > 0) {
            foreach ($locations as $key => $location) {
                $location = (array) $location;
                $relateUsers = $this->getLocationRealtedEmployees($location['id']);

                if (sizeof($relateUsers) > 0) {
                    foreach ($relateUsers as $key1 => $user) {
                        $user = (array) $user;
                        $userData = $this->getEmployeeRelatedUserByEmployeeId($user['id']);

                        if (!is_null($userData)) {
                            $userData = (array)$userData;
                            $idkey = 'u'.$userData['userId'];
                            $emails[$idkey] = $userData['email'];
                        }
                    }
                }
            }
        }

        return $emails;
    }

    /**
     * Following function retrives all user emails that has permission to perform next action in workflow.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *    "u25" => "test@bmail.com"
     * ]
     */
    private function getNextActionPerformerBaseUserEmails ($nextPerformActions, $instanceId) {
        $queryBuilder = $this->store->getFacade();
        $emails = [];

        if (empty($nextPerformActions)) {
            return $emails;
        }

        $instanceData = $queryBuilder::table('workflowInstance')
            ->select(
                'workflowInstance.*',
                'workflowDetail.employeeId',
            )
            ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
            ->where('workflowInstance.id', $instanceId)
            ->first();
        $instanceData = (array) $instanceData;

        $currentState = $instanceData['priorState'];
        $workflowId = $instanceData['workflowId'];

        //get retated transitions for actions
        $realtedNextTransitions = $queryBuilder::table('workflowStateTransitions')
            ->where('workflowId', $workflowId)
            ->where('priorStateId', $currentState)
            ->whereIn('actionId', $nextPerformActions)
            ->where('workflowStateTransitions.isDelete', 0)
            ->get();

        if (!is_null($realtedNextTransitions)) {
            $transitionPermitedUsersEmails = $this->getTransitionPermittedUserEmails($realtedNextTransitions, $instanceData['employeeId']);
            $emails = array_merge($emails,  $transitionPermitedUsersEmails);
        }
        return $emails;
    }


    /**
     * Following function retrives all user emails that has permission to perform next action in workflow.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *    "u25" => "test@bmail.com"
     * ]
     */
    private function getLevelWisePerformerBaseUserEmails ($instanceApprovalLevelData, $instanceId) {
        $queryBuilder = $this->store->getFacade();
        $emails = [];
        $employeeIds = [];
        $userIds = [];

        $instanceData = $queryBuilder::table('workflowInstance')
            ->select(
                'workflowInstance.*',
                'workflowDetail.employeeId',
            )
            ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
            ->where('workflowInstance.id', $instanceId)
            ->first();
        $instanceData = (array) $instanceData;

        if ($instanceApprovalLevelData['levelSequence'] == 0) {
            return [];
        }

        //get workflow level data by level sequence and workflow id
        $relatedWfApprovalLevelData = $queryBuilder::table('workflowApprovalLevel')
            ->where('workflowApprovalLevel.workflowId', $instanceData['workflowId'])
            ->where('workflowApprovalLevel.levelSequence', $instanceApprovalLevelData['levelSequence'])
            ->where('workflowApprovalLevel.isDelete', false)
            ->first();
        
            
        $relatedWfApprovalLevelData = (array) $relatedWfApprovalLevelData;
    
        switch ($relatedWfApprovalLevelData['levelType']) {
            case 'STATIC':
                array_push($employeeIds, $relatedWfApprovalLevelData['staticApproverEmployeeId']);
                break;
            case 'DYNAMIC':
                if (!empty($relatedWfApprovalLevelData['dynamicApprovalTypeCategory'])) {
                    switch ($relatedWfApprovalLevelData['dynamicApprovalTypeCategory']) {
                        case 'COMMON':
                            if (!empty($relatedWfApprovalLevelData['commonApprovalType'])) {
                                if ($relatedWfApprovalLevelData['commonApprovalType'] == 'REPORTING_PERSON') {
                                    //get the reporting person of requester
                                    $managerId = $this->getManagerId($instanceData['employeeId']);
                                    array_push($employeeIds, $managerId);
                                }
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
                                if (sizeof($employeeIdArray) > 0) {
                                    $employeeIds = array_merge($employeeIds, $employeeIdArray);
                                }
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

                                if (sizeof($employeeIdArray) > 0) {
                                    $employeeIds = array_merge($employeeIds, $employeeIdArray);
                                }

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

                                if (sizeof($userIdArray) > 0) {
                                    $userIds = $userIdArray;
                                }
                            } 
                            break;
                        
                        default:
                            # code...
                            break;
                    }
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

                if (sizeof($employeeIdArray) > 0) {
                    $employeeIds = array_merge($employeeIds, $employeeIdArray);
                }
                break;
            
            default:
                # code...
                break;
        }

        $userIdArray = [];

        //get emnployee related user emails
        if (sizeof($employeeIds) > 0) {
            $dataSet = DB::table('user')
                ->where('user.isDelete', false)
                ->whereNotNull('employeeId')
                ->whereIn('employeeId', $employeeIds)
                ->pluck('user.id')->toArray();

            if (sizeof($dataSet) > 0) {
                $userIdArray = $dataSet;
            }

        }

        //get emnployee related user emails
        if (sizeof($userIds) > 0) {
            $dataSet = DB::table('user')
                ->where('user.isDelete', false)
                ->whereIn('id', $userIds)
                ->pluck('user.id')->toArray();
        
            if (sizeof($dataSet) > 0) {
                if (sizeof($userIdArray) > 0) {
                    $userIdArray = array_merge($userIdArray,$dataSet);
                } else {
                    $userIdArray = $dataSet;
                }
            }
        }

        $userIdArray = array_values(array_unique($userIdArray));

        $userDataSet = DB::table('user')
            ->where('user.isDelete', false)
            ->whereIn('id', $userIdArray)->get();

        foreach ($userDataSet as $key1 => $userData) {
            $userData = (array)$userData;
            $idkey = 'u'.$userData['id'];
            $emails[$idkey] = $userData['email'];
        }

        return $emails;
    }

    /**
     * Following function retrives user emails that has permission to perform perticular transition.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *    "u25" => "test@bmail.com"
     * ]
     */
    private function getTransitionPermittedUserEmails($realtedNextTransitions, $employeeId) 
    {

        $emails = [];
        foreach ($realtedNextTransitions as $key => $transition) {
            $transition = (array) $transition;

            if ($transition['permissionType'] == 'ROLE_BASE') {
                $permitedRoles = (!empty($transition['permittedRoles'])) ? json_decode($transition['permittedRoles']) : [];

                if (empty($permitedRoles)) {
                    continue;
                }

                $roleRelatedUserEmails = [];

                foreach ($permitedRoles as $rolekey => $role) {
                    switch ($role) {
                        case 1: //ADMIN ROLE TYPE
                            $query = "SELECT  user.email, user.id as userId FROM user WHERE adminRoleId  IS NOT NULL;";
                            $relateUsers = DB::select($query);
                        
                            if (sizeof($relateUsers) > 0) {
                                foreach ($relateUsers as $userkey => $user) {
                                    $user = (array) $user;
                                    $idKey = 'u'.$user['userId'];
                                    if (!isset($roleRelatedUserEmails[$idKey])) {
                                        $roleRelatedUserEmails[$idKey] = $user['email'];
                                    }
                                }
                            }
                            break;
                        case 2: //EMPLOYEE ROLE TYPE
                            $userData = $this->getEmployeeRelatedUserByEmployeeId($employeeId);

                            if (!is_null($userData)) {
                                $userData = (array)$userData;
                                $idKey = 'u'.$userData['userId'];
                                if (!isset($roleRelatedUserEmails[$idKey])) {
                                    $roleRelatedUserEmails[$idKey] = $userData['email'];
                                }
                            }
                            break;
                        case 3: //MAMAGER ROLE TYPE
                            $managerId = $this->getManagerId($employeeId);
                   
                            if (!is_null($managerId)) {
                    
                                $userData = $this->getEmployeeRelatedUserByEmployeeId($managerId);

                                if (!is_null($userData)) {
                                    $userData = (array)$userData;
                                    $idKey = 'u'.$userData['userId'];
                                    if (!isset($roleRelatedUserEmails[$idKey])) {
                                        $roleRelatedUserEmails[$idKey] = $userData['email'];
                                    }
                                }
                            }
                            
                            break;
                        
                        default:
                            break;
                    }
                }

                $emails = array_merge($emails,  $roleRelatedUserEmails);
            } elseif ($transition['permissionType'] == 'EMPLOYEE_BASE') {

                $permitedEmployees = (!empty($transition['permittedEmployees'])) ? json_decode($transition['permittedEmployees']) : [];

                if (empty($permitedEmployees)) {
                    continue;
                }

                $employeeRelatedUserEmails = [];

                foreach ($permitedEmployees as $empkey => $employee) {
                    $userData = $this->getEmployeeRelatedUserByEmployeeId($employee);

                    if (!is_null($userData)) {
                        $userData = (array)$userData;
                        $idKey = 'u'.$userData['userId'];
                        if (!isset($employeeRelatedUserEmails[$idKey])) {
                            $employeeRelatedUserEmails[$idKey] = $userData['email'];
                        }
                    }

                }

                $emails = array_merge($emails,  $employeeRelatedUserEmails);
            }

        }

        return $emails;

    }

    private function getLocationRealtedEmployees($locationId)
    {
        $queryBuilder = $this->store->getFacade();
        
        $employeeCurrentJob = $queryBuilder::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
        ->where('employeeJob.locationId', $locationId)->get();
        
        return is_null($employeeCurrentJob) ? null : $employeeCurrentJob;
    }

    private function processSelectedIds($idString) {
        $strArr = str_split($idString);
        $data['section'] = null;
        $data['id'] = null;
        
        $explodeArr = explode($strArr[0],$idString);
        $data['id'] = $explodeArr[1];
        switch ($strArr[0]) {
            case 'u':
                $data['section'] = 'user';
                break;
            case 'r':
                $data['section'] = 'role';
                break;
            case 'd':
                $data['section'] = 'department';
                break;
            case 'l':
                $data['section'] = 'location';
                break;
            default:
                # code...
                break;
        }

        return $data;
    }

    private function getManagerId($employeeId)
    {
        $queryBuilder = $this->store->getFacade();

        $employeeCurrentJob = $queryBuilder::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->where('employeeId', $employeeId)->first(['reportsToEmployeeId']);

        return is_null($employeeCurrentJob) ? null : $employeeCurrentJob->reportsToEmployeeId;
    }

    private function getEmployeeRelatedUserByEmployeeId($employeeId)
    {
        $queryBuilder = $this->store->getFacade();

        $employeeRelateUser = $queryBuilder::table('employee')->select(
            'user.email',
            'user.id as userId'
        )->leftJoin('user', 'user.employeeId', '=', 'employee.id')
            ->where('employeeId', $employeeId)->first(['email']);

        return is_null($employeeRelateUser) ? null : $employeeRelateUser;
    }

      /** 
     * Get document template with db values.
     * 
     * @param $templateId document template id
     * @param $employeeId employee id
     * 
     * Sample output: 
     * [
     *      error => false,
     *      msg => "success",
     *      data => "<h3>Hi smith !!!</h3>"
     * ]
     */
    public function generateDocumentContent($templateId, $employeeId, $relatedActionId, $instanceId = null, $workflowContextId= null, $actionPerformerDetails = [])
    {
        try {
            $db = $this->store->getFacade();
            $emailTemplate = $db::table('emailTemplateContent')->where('id', $templateId)->where('isDelete', false)->first();

            if (is_null($emailTemplate)) {
                return ['error' => true, 'msg' => Lang::get('documentTemplateMessages.basic.ERR_NONEXISTENT'), 'data' => null];
            }

            // get defined tokens in the content
            preg_match_all("/{#(.*?)#}/", $emailTemplate->content, $matches);

            // get tokens
            $placeHolders = $matches[0];
            $tokenNames = $matches[1];
            $notFilledTokens = $tokenNames;
            $filledValues = array_fill_keys($tokenNames, null);

            if (!empty($instanceId) && !empty($workflowContextId)) {
                $filledValues = $this->getFilledValuesAccordingToWorkflow($workflowContextId, $instanceId, $relatedActionId, $actionPerformerDetails);
            }
            
            // remove duplicate tokens
            $placeHolders = array_unique($placeHolders);

            $templatePlaceHolders = [];

            array_walk($placeHolders, function($placeHolder) use (&$templatePlaceHolders, $filledValues) {
                $key = str_replace(['#','{','}'], '', $placeHolder);
                $templatePlaceHolders[$placeHolder] = isset($filledValues[$key]) ? $filledValues[$key] : $placeHolder;
            });

            $content = str_replace(array_keys($templatePlaceHolders), array_values($templatePlaceHolders), $emailTemplate->content, $count);

            return ['error' => false, 'msg' => 'success', 'data' => ['content' => $content]];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ['error' => true, 'msg' => $e->getMessage(), 'data' => null];
        }
    }

    private function getFilledValuesAccordingToWorkflow($workflowContextId, $instanceId, $relatedActionId, $actionPerformerDetails = []) {
        $filledValues = [];
        
        //get workflow instance data
        $queryBuilder = $this->store->getFacade();
        $workflowData = $queryBuilder::table('workflowInstance')
        ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
        ->where('workflowInstance.id', $instanceId)
        ->first();
        $workflowData = (array) $workflowData;
        
        $details = json_decode($workflowData['details']);
        $details = (array) $details;
        
        //get request Related employee
        $employeeModel = $this->getModel('employee', true);
        $employeeData = $queryBuilder::table('employee')
            ->where('employee.id', $workflowData['employeeId'])
            ->first();
        $employeeData = (array) $employeeData;
        $filledValues['employee_first_name'] = $employeeData['firstName'];
        $filledValues['employee_last_name'] = $employeeData['lastName'];
        $filledValues['employee_full_name'] = $employeeData['firstName'].' '.$employeeData['lastName'];
        $filledValues['employee_number'] = $employeeData['employeeNumber'];
        

        
        //set action performer details
        //get perform user
        $performUser = $queryBuilder::table('user')->where('id', $actionPerformerDetails['performBy'])->where('isDelete', false)->first();
        
        //get perform Action Data
        $performActionData = $queryBuilder::table('workflowAction')->where('id', $actionPerformerDetails['performAction'])->where('isDelete', false)->first();

        $performUser = (array) $performUser;
        $performActionData = (array) $performActionData;
        $filledValues['workflow_action_performer_first_name'] = $performUser['firstName'];
        $filledValues['workflow_action_performer_last_name'] = $performUser['lastName'];
        $filledValues['workflow_action_performer_full_name'] = $performUser['firstName'].' '.$performUser['lastName'];
        // $filledValues['workflow_action'] = $performActionData['label'];
        
        
        switch ($workflowContextId) {
            case 1:
                
                $filledValues['related_section'] = $details['tabName'];
                $filledValues['related_action'] = $details['relateAction'];
                break;
            case 2:                
                $leaveTypeModel = $this->getModel('leaveType', true);
                $leaveTypeData = (array) $this->store->getById($leaveTypeModel, $details['leaveTypeId']);
                
                $fromDateTime = (!empty($details['fromTime'])) ? $details['fromDate'].' '.$details['fromTime'] : $details['fromDate'];
                $toDateTime = (!empty($details['toTime'])) ? $details['toDate'].' '.$details['toTime'] : $details['toDate'];
                
                $filledValues['leave_from_date_time'] = $fromDateTime;
                $filledValues['leave_to_date_time'] = $toDateTime;
                $filledValues['leave_type'] = $leaveTypeData['name'];
                $filledValues['num_of_leave_dates'] = (is_null($details['numberOfLeaveDates'])) ? '-' : $details['numberOfLeaveDates'];
                $filledValues['reason'] = (!empty($details['reason'])) ? $details['reason'] : '-';
                
                break;
            case 3:

                $inDate = $details['inDateTime'] ? date('Y-m-d', strtotime($details['inDateTime'])) : '-';
                $inTime = $details['inDateTime'] ? date('h:i A', strtotime($details['inDateTime'])) : '-';
                $outDate = $details['outDateTime'] ? date('Y-m-d', strtotime($details['outDateTime'])) : '-';
                $outTime = $details['outDateTime'] ? date('h:i A', strtotime($details['outDateTime'])) : '-';

                $filledValues['in_date_time'] = $inDate.' '.$inTime;
                $filledValues['out_date_time'] = $outDate.' '.$outTime;
                $filledValues['reason'] = (!empty($details['reason'])) ? $details['reason'] : '-';
                break;
            case 4:                                
                $date = (!empty($details['date'])) ? $details['date'] : '-';
                $fromTime = (!empty($details['fromTime'])) ? $details['fromTime'] : '-';
                $toTime = (!empty($details['toTime'])) ? $details['toTime'] : '-';
                                
                $filledValues['short_leave_date'] = $date;
                $filledValues['short_leave_from_time'] = $fromTime;
                $filledValues['short_leave_end_time'] = $toTime;
                $filledValues['leave_type'] = 'Short Leave';
                $filledValues['reason'] = (!empty($details['reason'])) ? $details['reason'] : '-';
                
                break;
            case 5:                                
                $shiftDate = (!empty($details['shiftDate'])) ? $details['shiftDate'] : '-';

                $workshiftModel =  $this->getModel('workShifts', true);
                $newShiftData = (array) $this->store->getById($workshiftModel, $details['newShiftId']);
                $previousShiftData = (array) $this->store->getById($workshiftModel, $details['currentShiftId']);
                                
                $filledValues['shift_change_applicable_date'] = $shiftDate;
                $filledValues['previous_shift'] = $previousShiftData['name'];
                $filledValues['new_shift'] = $newShiftData['name'];
                $filledValues['reason'] = (!empty($details['reason'])) ? $details['reason'] : '-';
                
                break;
            case 6:    
                $leaveRequestModel = $this->getModel('leaveRequest', true);     
                $orinalLeaveData = (array) $this->store->getById($leaveRequestModel, $details['leaveRequestId']);  

                
                $orgfromDateTime = (!empty($orinalLeaveData['fromDate'])) ? $orinalLeaveData['fromDate'] : '-';
                $orgToDateTime = (!empty($orinalLeaveData['toDate'])) ? $orinalLeaveData['toDate']: '-';
                $cancelLeaveDetails = $details['cancelDatesDetails'];
                $canceledLeaveCount = 0;
                foreach ($cancelLeaveDetails as $key => $cancelLeaveDateData) {
                    $cancelLeaveDateData = (array) $cancelLeaveDateData;
                    if ($cancelLeaveDateData['isCheckedFirstHalf'] && $cancelLeaveDateData['isCheckedSecondHalf']) {
                        $canceledLeaveCount += 1;
                        continue;
                    }

                    if ($cancelLeaveDateData['isCheckedFirstHalf'] || $cancelLeaveDateData['isCheckedSecondHalf']) {
                        $canceledLeaveCount += 0.5;
                        continue;
                    }
                }

                $filledValues['original_leave_from_date'] = $orgfromDateTime;
                $filledValues['original_leave_end_date'] = $orgToDateTime;
                $filledValues['applied_leave_count'] = (is_null($orinalLeaveData['numberOfLeaveDates'])) ? '-' : $orinalLeaveData['numberOfLeaveDates'];
                $filledValues['cancelled_leave_count'] = $canceledLeaveCount;
                $filledValues['reason'] = (!empty($details['cancelReason'])) ? $details['cancelReason'] : '-';
                
                break;
            case 7:     
                $resignationTypeId = $details['resignationTypeId'];
                $status = '';

                if (isset($details['resignationNoticePeriodRemainingDays']) && $details['resignationNoticePeriodRemainingDays'] == 0) {
                    $status = 'Completed';
                }

                if (isset($details['resignationNoticePeriodRemainingDays']) && $details['resignationNoticePeriodRemainingDays'] > 0) {
                    $status = 'Not Completed';
                }

                $resignationType = (array)$queryBuilder::table('resignationType')->where('id', $resignationTypeId)->first();
                $filledValues['resignation_handover_date'] = $details['resignationHandoverDate'];
                $filledValues['resignation_last_working_date'] = $details['lastWorkingDate'];
                $filledValues['resignation_effective_date'] = $details['updatedEffectiveDate'];
                $filledValues['resignation_type'] = (!empty($resignationType['name'])) ?  $resignationType['name'] : '-';
                $filledValues['resignation_notice_period_complete_status'] = $status;
                $filledValues['reason'] = (!empty($details['resignationReason'])) ? $details['resignationReason'] : '-';
                
                break;
            case 8:     
                

                $shortLeaveRequestModel = $this->getModel('shortLeaveRequest', true);     
                $orinalShortLeaveData = (array) $this->store->getById($shortLeaveRequestModel, $details['shortLeaveRequestId']);  

                $date = (!empty($orinalShortLeaveData['date'])) ? $orinalShortLeaveData['date'] : '-';
                $fromTime = (!empty($orinalShortLeaveData['fromTime'])) ? $orinalShortLeaveData['fromTime'] : '-';
                $toTime = (!empty($orinalShortLeaveData['toTime'])) ? $orinalShortLeaveData['toTime'] : '-';
                                
                $filledValues['short_leave_date'] = $date;
                $filledValues['short_leave_from_time'] = $fromTime;
                $filledValues['short_leave_end_time'] = $toTime;
                $filledValues['leave_type'] = 'Short Leave';
                $filledValues['reason'] = (!empty($orinalShortLeaveData['reason'])) ? $orinalShortLeaveData['reason'] : '-';
                
                break;
            case 9:     
        
                $claimTypeModel = $this->getModel('claimType', true);     
                $orinalClaimTypeData = (array) $this->store->getById($claimTypeModel, $details['claimTypeId']);  

                $financialYearModel = $this->getModel('financialYear', true);     
                $financialYearData = (array) $this->store->getById($financialYearModel, $details['financialYearId']);  

                $date = (!empty($orinalShortLeaveData['date'])) ? $orinalShortLeaveData['date'] : '-';
                $fromTime = (!empty($orinalShortLeaveData['fromTime'])) ? $orinalShortLeaveData['fromTime'] : '-';
                $toTime = (!empty($orinalShortLeaveData['toTime'])) ? $orinalShortLeaveData['toTime'] : '-';
                                
                $filledValues['claim_type'] = (!empty($orinalClaimTypeData) && !empty($orinalClaimTypeData['typeName'])) ?  $orinalClaimTypeData['typeName'] : '-';
                $filledValues['financial_year'] = (!empty($financialYearData) && !empty($financialYearData['financialDateRangeString'])) ? $financialYearData['financialDateRangeString'] : '-';
                $filledValues['total_receipt_amount'] = !is_null($details['totalReceiptAmount']) ? $details['totalReceiptAmount'] : '0.00';
                $filledValues['claim_month'] = $details['claimMonth'] ? $details['claimMonth'] : '-'  ;
                
                break;
            case 10:     
    
                $monthString = $details['month'];
                $arr = explode('/', $monthString);
                $month = $arr[1];
                $year = $arr[0];
                $requestedOtHour = intdiv($details['totalRequestedOtMins'], 60);
                $requestedOtMinutes = ($details['totalRequestedOtMins'] % 60);

                $requestedHourString = $requestedOtHour >= 10 ? $requestedOtHour : '0'.$requestedOtHour;
                $requestedMinString = $requestedOtMinutes >= 10 ? $requestedOtMinutes : '0'.$requestedOtMinutes;
                $requestedOtString = $requestedHourString.':'.$requestedMinString;
                


                //get related attendance detail
                $postOtRequestDetails = $queryBuilder::table('postOtRequestDetail')->where('postOtRequestId', $details['id'])->get();

                $requestedOtDatesCount = sizeof($postOtRequestDetails);

                $totalApprovedOt = 0;

                foreach ($postOtRequestDetails as $key => $value) {
                    $value = (array) $value;

                    $totalApprovedOt += !is_null($value['totalApprovedOt']) ? $value['totalApprovedOt'] : 0;
                    
                }

                $totalApprovedOtHour = intdiv($totalApprovedOt, 60);
                $totalApprovedOtMin = ($totalApprovedOt % 60);
                $totalApprovedOtHourString = $totalApprovedOtHour >= 10 ? $totalApprovedOtHour : '0'.$totalApprovedOtHour;
                $totalApprovedOtMinString = $totalApprovedOtMin >= 10 ? $totalApprovedOtMin : '0'.$totalApprovedOtMin;
                $totalApprovedOtString = $totalApprovedOtHourString.':'.$totalApprovedOtMinString;


                $filledValues['post_ot_request_month'] = (!empty($month)) ?  $month : '-';
                $filledValues['post_ot_request_year'] = (!empty($year)) ?  $year : '-';
                $filledValues['total_requested_ot'] = $requestedOtString;
                $filledValues['total_approved_ot'] = (!empty($totalApprovedOtString)) ?  $totalApprovedOtString : '-';
                $filledValues['num_of_requested_dates'] = (!empty($requestedOtDatesCount)) ?  $requestedOtDatesCount : '-';
                
                break;
                    
            default:
                # code...
                break;
        }

        return $filledValues;
    }


    public function renderRecipientDetails($ContentData,$userId)
    {
        
        // get defined tokens in the content
        preg_match_all("/{#(.*?)#}/", $ContentData, $matches);

        // get tokens
        $placeHolders = $matches[0];
        $tokenNames = $matches[1];
        $notFilledTokens = $tokenNames;
        $filledValues = array_fill_keys($tokenNames, null);

        $userModel = $this->getModel('user', true);
        $userData = (array) $this->store->getById($userModel, $userId);
        $userData = (array) $userData;

        $filledValues['recipient_first_name'] = $userData['firstName'];
        $filledValues['recipient_last_name'] = $userData['lastName'];
        $filledValues['recipient_full_name'] = $userData['firstName'].' '.$userData['lastName'];
        $filledValues['recipient_email'] = $userData['email'];

        
        // remove duplicate tokens
        $placeHolders = array_unique($placeHolders);
        
        $templatePlaceHolders = [];
        
        array_walk($placeHolders, function($placeHolder) use (&$templatePlaceHolders, $filledValues) {
            $key = str_replace(['#','{','}'], '', $placeHolder);
            $templatePlaceHolders[$placeHolder] = isset($filledValues[$key]) ? $filledValues[$key] : $placeHolder;
        });
        
        $content = str_replace(array_keys($templatePlaceHolders), array_values($templatePlaceHolders), $ContentData, $count);

        return $content;

    }

}
