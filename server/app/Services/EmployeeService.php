<?php

namespace App\Services;

use Log;
use App\Exceptions\Exception;
use App\Exceptions\FileStoreException;
use App\Exceptions\FileStoreNotFoundException;
use App\Jobs\BackDatedAttendanceProcess;
use App\Jobs\EmployeeActiveStatusHandlingJob;
use App\Jobs\LeaveAccrualBackdatedJob;
use App\Library\ActiveDirectory;
use App\Library\AzureUser;
use App\Library\FileStore;
use App\Library\ModelValidator;
use App\Library\Redis;
use App\Library\RelationshipType;
use App\Library\Session;
use App\Library\Store;
use App\Traits\AttendanceProcess;
use App\Traits\ConfigHelper;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;
use App\Traits\EmployeeHelper;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Collection;

/**
 * Name: EmployeeService
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the EmployeeController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Hashan
 */
class EmployeeService extends BaseService
{
    use JsonModelReader;
    use EmployeeHelper;
    use ConfigHelper;

    private $employeeModel;
    private $workflowModel;
    private $userModel;

    private $store;
    private $session;
    private $fileStorage;
    private $redis;
    private $workflowService;
    private $workflowInstanceModel;
    private $modelService;
    private $autoGenrateIdService;
    private $activeDirectory;
    private $azureUser;
    private $paymentService;

    public function __construct(
        Store $store,
        Session $session,
        Redis $redis,
        FileStore $fileStorage,
        WorkflowService $workflowService,
        ModelService $modelService,
        AutoGenerateIdService $autoGenerateIdService,
        ActiveDirectory $activeDirectory,
        AzureUser $azureUser,
        PaymentService $paymentService
    ) {
        $this->store = $store;
        $this->session = $session;
        $this->fileStorage = $fileStorage;
        $this->workflowService = $workflowService;
        $this->modelService = $modelService;
        $this->redis = $redis;
        $this->activeDirectory = $activeDirectory;
        $this->azureUser = $azureUser;
        $this->employeeModel = $this->getModel('employee', true);
        $this->workflowModel = $this->getModel('workflowDefine', true);
        $this->userModel = $this->getModel('user', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->autoGenrateIdService = $autoGenerateIdService;
        $this->paymentService = $paymentService;
    }

    /**
     * Following function creates a user role. The user role details that are provided in the Request
     * are extracted and saved to the user role table in the database. user_role_id is auto genarated and title
     * are identified as unique.
     *
     * @param $employee array containing the user role data
     * @return int | String | array
     *
     * Usage:
     * $employee => [
     *
     * ]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Employee created successfully!",
     * $data => {"title": "LK HR", ...} //$data has a similar set of values as the input
     *  */

    public function createEmployee($employee)
    {
        try {

            $result = $this->paymentService->validatePaymentStatus();
            if ($result['error']) {
                return $this->error(400, $result['message'], null);
            }

            $employeeNumber = null;
            $job = isset($employee['jobs']) && $employee['jobs'] > 0 ? $employee['jobs'][0] : null;
            $entityId = !is_null($job) ? $job['orgStructureEntityId'] : null;
            $employeeNumberResponse = $this->generateEmployeeNumber($entityId);

            if ($employeeNumberResponse['error']) {
                return $this->error(400, $employeeNumberResponse['message'], null);
            }

            $employeeNumber = $employeeNumberResponse['data']['employeeNumber'];
            $employeeNumberConfigId = $employeeNumberResponse['data']['numberConfigId'];

            if (is_null($employeeNumber)) {
                return $this->error(400, Lang::get('employeeMessages.basic.INVALID_EMP_NUMBER_FORMAT'), null);
            }

            $employee['employeeNumber'] = $employeeNumber;
            $employee['recentHireDate'] = $employee['hireDate'];

            $validationResponse = $this->employeeCreateValidation($employee);
            if (isset($validationResponse['error']) && $validationResponse['error']) {
                return $validationResponse;
            }

            $user = null;
            if (!empty($employee['user'])) {
                $user = $employee['user'];
                unset($employee['user']);
            }

            // azure related validation
            $isActiveAzureUserProvisioning = $this->getConfigValue('is_active_azure_user_provisioning');
            if ($isActiveAzureUserProvisioning) {
                $this->activeDirectory->initClient(
                    $this->getConfigValue('azure_tenant_id'),
                    $this->getConfigValue('azure_client_id'),
                    $this->getConfigValue('azure_client_secret')
                );

                $azureDomain = $this->getConfigValue('azure_domain_name');
                [$_, $emailDomain] = explode('@', $employee['workEmail']);

                if ($emailDomain !== $azureDomain) {
                    return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), [
                        'workEmail' => [Lang::get('employeeMessages.basic.EMAIL_DOMAIN_NOT_MATCH_WITH_AZURE_DOMAIN')]
                    ]);
                }

                try {
                    $this->activeDirectory->getUser($employee['workEmail']);
                    return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), [
                        'workEmail' => [Lang::get('employeeMessages.basic.EMAIL_ALREADY_EXIST_IN_AZURE')]
                    ]);
                } catch (Exception $e) {
                }
            }

            $newEmployee = $this->store->insert($this->employeeModel, $employee, true);

            if (!empty($user)) {
                $employeeId = $newEmployee['id'];
                $user['employeeId'] = $employeeId;
                $userService = new UserService($this->store, $this->session);
                $insertedUser = $userService->createUser($user);
                $newEmployee['user'] = $insertedUser;
            }

            $this->incrementEmployeeNumber($employeeNumberConfigId);

            $this->handleEmployeeActiveStatus($newEmployee['id']);

            $response = $this->getEmployee($newEmployee['id']);

            $this->handleBackdatedLeaveAccrual($newEmployee);

            // $this->handleBackdatedAttendance($newEmployee);

            if ($isActiveAzureUserProvisioning) {
                $azureCreateUser = $this->azureUser->parseCreateBody($employee, $this->getConfigValue('azure_default_password'));
                $azureCreateResponse = $this->activeDirectory->createUser($azureCreateUser);

                $azureUpdateUser = $this->azureUser->parseUpdateBody($employee);
                $azureUpdateResponse = $this->activeDirectory->updateUser($azureCreateResponse->getId(), $azureUpdateUser);
            }



            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_CREATE'), $response['data']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives all employees.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All employees retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllEmployees($permittedFields, $options)
    {
        try {
            // get permitted employee ids
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
            $customWhereClauses = ['where' => ['employee.isDelete' => false], 'whereIn' => ['id' => $permittedEmployeeIds]];

            $employees = $this->store->getAll(
                $this->employeeModel,
                $permittedFields,
                $options,
                ['gender', 'currentJobs'],
                $customWhereClauses
            );

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single employee for a provided employee_id.
     *
     * @param $id user employee id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getEmployee($id, $bypassFieldLevelAccess = false)
    {
        try {
            $employee = $this->store->getById(
                $this->employeeModel,
                $id,
                ['*'],
                [
                    'jobs',
                    'salaries',
                    'bankAccounts',
                    'dependents',
                    'experiences',
                    'educations',
                    'competencies',
                    'emergencyContacts',
                    'user',
                    'gender'
                ],
                $bypassFieldLevelAccess
            );
            if (empty($employee)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (!empty($employee->updatedAt)) {
                $employee->updatedAt = $this->getFormattedDateForList($employee->updatedAt);
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET'), $employee);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET'), null);
        }
    }


    /**
     * Following function check whether employee is allocated for a shift or not.
     *
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function checkIsShiftAllocated($id)
    {
        try {
            $shiftEmployeesIds = $this->store->getFacade()::table('employeeShift')->pluck('employeeId')->toArray();
            $workPatternEmployeesIds = $this->store->getFacade()::table('employeeWorkPattern')->where('isActivePattern', true)->pluck('employeeId')->toArray();

            $allShiftAssignEmployees = array_merge($shiftEmployeesIds, $workPatternEmployeesIds);

            $allShiftAssignEmployees = array_values(array_unique($allShiftAssignEmployees));

            $isHaveShift = in_array($id, $allShiftAssignEmployees) ? true : false;

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET'), ['isHaveShift' => $isHaveShift]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function return the formatted date for given time stamp
     *
     * @return | String
     *  */
    private function getFormattedDateForList($date)
    {
        try {
            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;

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
     * Following function updates a employee.
     *
     * @param $id user employee id
     * @param $employee array containing employee data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateEmployee($id, $employee, $workflow = false)
    {
        try {
            DB::beginTransaction();
            $validationResponse = ModelValidator::validate($this->employeeModel, $employee, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            $existingEmployee = $this->store->getById($this->employeeModel, $id);
            if (empty($existingEmployee)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingEmployee = (array) $existingEmployee;

            // azure related validation
            $isActiveAzureUserProvisioning = $this->getConfigValue('is_active_azure_user_provisioning');
            if ($isActiveAzureUserProvisioning) {
                $this->activeDirectory->initClient(
                    $this->getConfigValue('azure_tenant_id'),
                    $this->getConfigValue('azure_client_id'),
                    $this->getConfigValue('azure_client_secret')
                );

                $azureDomain = $this->getConfigValue('azure_domain_name');
                [$_, $emailDomain] = explode('@', $employee['workEmail']);

                if ($emailDomain !== $azureDomain) {
                    return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), [
                        'workEmail' => [Lang::get('employeeMessages.basic.EMAIL_DOMAIN_NOT_MATCH_WITH_AZURE_DOMAIN')]
                    ]);
                }

                try {
                    $existingAzureUser = $this->activeDirectory->getUser($employee['workEmail']);
                    Log::error('$existingAzureUser > ' . json_encode($existingAzureUser));
                } catch (Exception $e) {
                }
            }

            //check the Employee Number format
            // if (!empty($employee['employeeNumber'])) {
            //     $employeeNumber = $this->checkEmployeeNumberFormat($employee['employeeNumber']);

            //     if (is_null($employeeNumber)) {
            //         return $this->error(400, Lang::get('employeeMessages.basic.INVALID_EMP_NUMBER_FORMAT'), null);
            //     }
            // }

            if (!empty($employee['workEmail']) && !empty($employee['id'])) {
                //get employee not link users
                $employeeNotLinkCollection = $this->store->getFacade()::table('user')->whereNull('employeeId')->get()->toArray();
                $usersNotLinkedWithUpdatedEmployeeCollection = $this->store->getFacade()::table('user')->where('employeeId', '!=', $employee['id'])->get()->toArray();

                $collection = collect($usersNotLinkedWithUpdatedEmployeeCollection);
                $mergedUsers = $collection->merge($employeeNotLinkCollection);


                $filteredUsersCount = $mergedUsers->where('email', $employee['workEmail'])->count();

                if ($filteredUsersCount > 0) {
                    DB::rollback();
                    return $this->error('500', Lang::get('employeeMessages.basic.ERR_DUPLICATE_USER_EMAIL'), null);
                }
            }

            if ($workflow) {
                // this is the workflow context id related for Profile Update
                $context = 1;
                $modelAttributes = $this->employeeModel->getAttributes();
                $writeableFields = $this->session->permission->writeableFields($this->employeeModel->getName(), $modelAttributes);
                $filteredEmployeeData = [];

                foreach ($employee as $key => $field) {
                    if (in_array($key, $writeableFields)) {
                        $filteredEmployeeData[$key] = $field;
                    }
                }

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employee['id']);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;


                $filteredEmployeeData['tabName'] = 'Personal';
                $filteredEmployeeData['isMultiRecord'] = false;
                $filteredEmployeeData['relateAction'] = 'update';

                $finalStates = [];

                $relateRequestCount = $this->checkWhetherHasInprogressWorkflowRequests($id, $filteredEmployeeData, $finalStates, $context);
                if ($relateRequestCount > 0) {
                    DB::rollback();
                    return $this->error('500', Lang::get('employeeMessages.basic.ERR_HAS_REMAINING_INPROGRESS_WF'), $workflowDefineId);
                }

                //send this time change request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $filteredEmployeeData, $id);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }
                $employee = $this->getEmployee($id);
                DB::commit();
                return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GO_WF'), $employee['data']);
            }


            if (!empty($employee['workEmail']) && !empty($employee['id']) &&  $existingEmployee['workEmail'] != $employee['workEmail']) {
                $user = $this->store->getFacade()::table('user')
                    ->where('employeeId', $employee['id'])
                    ->update(['email' => $employee['workEmail']]);
            }

            //update middleName in User table
            if (isset($employee['middleName']) && !empty($employee['id'])  &&  $existingEmployee['middleName'] != $employee['middleName']) {
                $user = $this->store->getFacade()::table('user')
                    ->where('employeeId', $employee['id'])
                    ->update(['middleName' => $employee['middleName']]);
            }

            $response = $this->store->updateById($this->employeeModel, $id, $employee);

            if (!$response) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_UPDATE'), $id);
            }

            $this->handleEmployeeActiveStatus($id);

            if ($isActiveAzureUserProvisioning) {
                if (empty($existingAzureUser)) {
                    $azureCreateUser = $this->azureUser->parseCreateBody($employee, $this->getConfigValue('azure_default_password'));
                    $azureCreateResponse = $this->activeDirectory->createUser($azureCreateUser);
                    $azureUserid = $azureCreateResponse->getId();
                    Log::error('createUser > ' . $azureUserid);
                } else {
                    $azureUserid = $existingAzureUser->getId();
                    Log::error('existingAzureUser > ' . $azureUserid);
                }

                $azureUpdateUser = $this->azureUser->parseUpdateBody($employee);
                Log::error('azureUpdateUser > ' . json_encode($azureUpdateUser));
                $azureUpdateResponse = $this->activeDirectory->updateUser($azureUserid, $azureUpdateUser);
            }

            $response = $this->getEmployee($id);
            DB::commit();

            $employeeData = json_decode(json_encode($response['data']), true);
            if ($this->isHireDateUpdatedAndLessThanPrevious($existingEmployee, $employeeData)) {
                $this->handleBackdatedAttendance($employeeData);
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_UPDATE'), $response['data']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a employee.
     *
     * @param $id employee id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteEmployee($id)
    {
        try {
            $existingEmployee = $this->store->getById($this->employeeModel, $id);
            if (empty($existingEmployee)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            //get employee related user
            $relatedUser = $this->store->getFacade()::table('user')
                ->where('employeeId', $existingEmployee->id)->where('isDelete', false)->first();

            if (!empty($relatedUser)) {
                $relatedUser = (array) $relatedUser;
                //check whether this employee link with global admin user
                if (!empty($relatedUser['adminRoleId']) && $relatedUser['adminRoleId'] == 1) {
                    return $this->error(500, Lang::get('employeeMessages.basic.ERR_LINKED_WITH_GLOBAL_ADMIN_USER'), null);
                }
            }

            //get list of subordinate employees relate to deleted employees
            $reportedEmployeesCount =  $this->store->getFacade()::table($this->employeeModel->getName())
                ->Leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->where('employeeJob.reportsToEmployeeId', '=', $id)
                ->count();


            //check whether this employee has any subordinates
            if ($reportedEmployeesCount > 0) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_LINKED_WITH_SUBORDINATES'), null);
            }

            $result = $this->store->deleteById($this->employeeModel, $id, true);

            if (!$result) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_DELETE'), $id);
            }

            if (!empty($relatedUser)) {
                $deleteRelatedUser = $this->store->deleteById($this->userModel, $relatedUser['id'], true);
            }

            $this->paymentService->updateSubscription();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_DELETE'), $existingEmployee);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_DELETE'), null);
        }
    }

    // get next 10 birth days
    public function getUpcomingBirthDays()
    {
        try {
            $employees = DB::select('
            SELECT
                id,
                CONCAT_WS(" ", firstName,middleName, lastName) as name,
                (SELECT EXTRACT(DAY FROM dateOfBirth)) AS day,
                dateOfBirth,
                profilePicture,
                DATE_ADD(
                    dateOfBirth,
                    INTERVAL IF(DAYOFYEAR(dateOfBirth) >= DAYOFYEAR(CURDATE()),
                    YEAR(CURDATE())-YEAR(dateOfBirth),
                    YEAR(CURDATE())-YEAR(dateOfBirth)+1
                    ) YEAR
                ) AS next_birthday
            FROM employee
            WHERE
                dateOfBirth IS NOT NULL
            ORDER BY next_birthday
            LIMIT 10;
            ');

            $employeeData = [];
            foreach ($employees  as $employee) {
                $profilePic = '';
                if ($employee->profilePicture != 0) {
                    $profilePic = $this->fileStorage->getBase64EncodedObject($employee->profilePicture);
                }
                $employee->profilePic = !empty($profilePic) ? $profilePic->data : '';
                array_push($employeeData, $employee);
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employeeData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    // get next 25 hire dates
    public function getUpcomingAnniversaryDays()
    {
        try {
            $employees = DB::select('
                SELECT
                    id,
                    CONCAT_WS(" ", firstName,middleName, lastName) as name,
                    (SELECT EXTRACT(DAY FROM hireDate)) AS day,
                    hireDate as dateOfBirth,
                    profilePicture,
                    DATE_ADD(
                        hireDate,
                        INTERVAL IF(DAYOFYEAR(hireDate) >= DAYOFYEAR(CURDATE()),
                        YEAR(CURDATE())-YEAR(hireDate),
                        YEAR(CURDATE())-YEAR(hireDate)+1
                        ) YEAR
                    ) AS next_birthday
                FROM employee
                WHERE
                    hireDate IS NOT NULL
                ORDER BY next_birthday
                LIMIT 25;
                ');

            $employeeData = [];
            foreach ($employees  as $employee) {
                $profilePic = '';
                if ($employee->profilePicture != 0) {
                    $profilePic = $this->fileStorage->getBase64EncodedObject($employee->profilePicture);
                }
                $employee->profilePic = !empty($profilePic) ? $profilePic->data : '';
                array_push($employeeData, $employee);
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employeeData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    public function changeEmployeeActiveStatus($id, $data)
    {
        try {
            $employee = DB::table($this->employeeModel->getName())->where('id', $id);
            $employee = (array) $employee;

            if (empty($employee)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (isset($data["isActive"])) {
                $employee["isActive"] = $data["isActive"];
            }

            $result = $this->store->updateById($this->employeeModel, $id, $employee);

            if (!$result) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_CHANGE_ACTIVE_STATUS'), $id);
            }

            // change user status according to the employee status
            $this->changeEmployeeRelatedUserActiveStatus($id, $data["isActive"]);

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_CHANGE_ACTIVE_STATUS'), $employee);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeMessages.basic.ERR_CHANGE_ACTIVE_STATUS'), null);
        }
    }


    /**
     * Following function is use to change user active status according to the link Employee active status.
     *
     * @param $id employee id
     * @param $activeStatus active status
     * @return int | String | array
     *
     */
    public function changeEmployeeRelatedUserActiveStatus($id, $activeStatus)
    {
        try {

            $relatedUser = $this->store->getFacade()::table('user')
                ->where('employeeId', $id)->where('isDelete', false)->first();

            $relatedUser = (array) $relatedUser;

            if (!empty($relatedUser)) {

                $relatedUser['inactive'] = ($activeStatus) ? false : true;

                $result = $this->store->updateById($this->userModel, $relatedUser['id'], $relatedUser);

                if (!$result) {
                    return $this->error(404, Lang::get('userMessages.basic.ERR_CHANGE_ACTIVE_STATUS'), $id);
                }
            }

            return $this->success(200, Lang::get('userMessages.basic.SUCC_CHANGE_ACTIVE_STATUS'), $relatedUser);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('userMessages.basic.ERR_CHANGE_ACTIVE_STATUS'), null);
        }
    }

    // this function handle original hire date value also
    private function handleEmployeeActiveStatus($id)
    {
        try {
            $currentJobRecord = $this->store->getFacade()::table('employee')
                ->where('employee.id', $id)
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->leftJoin('employmentStatus', 'employmentStatus.id', '=', 'employeeJob.employmentStatusId')
                ->select('employeeJob.*', 'employmentStatus.category AS employmentStatusCategory')
                ->first();

            $hasResigned = !(empty($currentJobRecord)) && !(empty($currentJobRecord->employeeJourneyType)) && $currentJobRecord->employeeJourneyType == 'RESIGNATIONS';
            $isActive = !$hasResigned;

            if (!$hasResigned && !empty($currentJobRecord->effectiveDate)) {
                $previousJobRecord = $this->store->getFacade()::table('employeeJob')
                    ->leftJoin('employmentStatus', 'employmentStatus.id', '=', 'employeeJob.employmentStatusId')
                    ->where('employeeJob.effectiveDate', '<', $currentJobRecord->effectiveDate)
                    ->where('employeeJob.employeeId', $id)
                    ->select('employmentStatus.category AS employmentStatusCategory')
                    ->first();

                if (
                    !(empty($previousJobRecord))
                    && !(empty($previousJobRecord->employmentStatusCategory))
                    && $previousJobRecord->employmentStatusCategory == 'CONTRACT'
                ) {

                    $response = $this->store->updateById($this->employeeModel, $id, ['recentHireDate' => $currentJobRecord->effectiveDate]);
                    if (!$response) return $this->error(404, Lang::get('employeeMessages.basic.ERR_UPDATE'), $id);
                } 

                // $response = $this->store->updateById($this->employeeModel, $id, ['recentHireDate' => $currentJobRecord->effectiveDate]);
                // if (!$response) return $this->error(404, Lang::get('employeeMessages.basic.ERR_UPDATE'), $id);
            }

            $this->changeEmployeeActiveStatus($id, ['isActive' => $isActive]);

            $this->paymentService->updateSubscription();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET'), null);
        }
    }

    public function getEmployeesPerManager()
    {
        try {
            $permittedFields = ["employeeNumber", "firstName", "lastName"];
            $options = [];
            $employees = $this->store->getAll(
                $this->employeeModel,
                $permittedFields,
                $options
            );

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    public function getEmployeeFieldAccessPermission()
    {
        try {
            $fieldPermissions = $this->session->getPermission()->getFieldPermissions();
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_EMP_FIELD_PERMISSION'), $fieldPermissions);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_EMP_FIELD_PERMISSION'), null);
        }
    }

    public function createEmployeeMultiRecord($id, $multirecordAttribute, $data, $workflow = false)
    {
        try {
            DB::beginTransaction();
            $data['employeeId'] = $id;

            $relation = $this->employeeModel->getRelationType($multirecordAttribute);
            if ($relation != RelationshipType::HAS_MANY) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_CREATE_EMP_MULTI_RECORDS'), null);
            }

            $attribute = $this->employeeModel->getAttribute($multirecordAttribute);
            if (!isset($attribute['modelName']) || $attribute['type'] != 'model') {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_CREATE_EMP_MULTI_RECORDS'), null);
            }

            $multiRecordModel = $this->getModel($attribute['modelName'], true);

            // $data['mobilePhone'] = '94-0710497136';
            $validationResponse = ModelValidator::validate($multiRecordModel, $data);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            if ($workflow) {

                // this is the workflow context id related for Profile Update
                $context = 1;

                // $workflowDefineId = $workFlowId->id;
                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $id);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;

                $data['tabName'] = $multirecordAttribute;
                $data['isMultiRecord'] = true;
                $data['relateAction'] = 'create';
                $data['modelName'] = $attribute['modelName'];
                //send this time change request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $data, $id);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                DB::commit();
                return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GO_WF'), $data);
            }

            $response = $this->store->insert($multiRecordModel, $data, true);
            $this->updateEmployeeRecordUpdatedAtColumn($id);
            $this->store->handleEffectiveDateConsiderableValues($this->employeeModel, $id, null, [$multirecordAttribute]);
            $this->handleEmployeeActiveStatus($id);
            DB::commit();
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_CREATE_EMP_MULTI_RECORDS'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_CREATE_EMP_MULTI_RECORDS'), null);
        }
    }

    /**
     * Following function is use to create employee job record that relate to employee resignation
     *
     * @param $id employee id
     * @param $multirecordAttribute multirecord attribute (job)
     * @param $data reated data set
     * @return int | String | array
     *
     */
    public function createResignationRecordInEmployeeJobTable($id, $multirecordAttribute, $data, $workflow = false)
    {
        try {
            $data['employeeId'] = $id;

            $relation = $this->employeeModel->getRelationType($multirecordAttribute);
            if ($relation != RelationshipType::HAS_MANY) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_CREATE_EMP_MULTI_RECORDS'), null);
            }

            $attribute = $this->employeeModel->getAttribute($multirecordAttribute);
            if (!isset($attribute['modelName']) || $attribute['type'] != 'model') {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_CREATE_EMP_MULTI_RECORDS'), null);
            }

            $multiRecordModel = $this->getModel($attribute['modelName'], true);

            // $data['mobilePhone'] = '94-0710497136';
            $validationResponse = ModelValidator::validate($multiRecordModel, $data);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            if (isset($data['fileType'])) {
                unset($data['attachDocument']);
                unset($data['fileType']);
                unset($data['fileSize']);
                unset($data['fileName']);
                unset($data['data']);
            }
            // $response = $this->store->insert($multiRecordModel, $data, true);
            $jobId = DB::table('employeeJob')->insertGetId($data);
            $response = DB::table('employeeJob')->where('id', $jobId)->first();

            $this->updateEmployeeRecordUpdatedAtColumn($id);
            $this->store->handleEffectiveDateConsiderableValues($this->employeeModel, $id, null, [$multirecordAttribute]);
            $this->handleEmployeeActiveStatus($id);
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_CREATE_EMP_MULTI_RECORDS'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_CREATE_EMP_MULTI_RECORDS'), null);
        }
    }


    public function updateEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId, $data, $workflow = false)
    {
        try {
            DB::beginTransaction();
            $data['employeeId'] = $id;
            $data['id'] = $multirecordId;
            $relation = $this->employeeModel->getRelationType($multirecordAttribute);
            if ($relation != RelationshipType::HAS_MANY) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET'), null);
            }

            $attribute = $this->employeeModel->getAttribute($multirecordAttribute);
            if (isset($attribute['modelName']) && $attribute['type'] != 'model') {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET'), null);
            }

            $multiRecordModel = $this->getModel($attribute['modelName'], true);

            $validationResponse = ModelValidator::validate($multiRecordModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            $multiRecordModelName = $multiRecordModel->getName();
            if ($multiRecordModelName == 'employeeExperience') {

                if (!empty($data['to']) && !empty($data['from'])) {
                    $fieldDefinitions = $multiRecordModel->getAttributes(false);
                    $fieldDefinition = $fieldDefinitions['from'];
                    $validations = $fieldDefinition['validations'] ?? null;

                    if ($fieldDefinition['type'] == 'timestamp' && isset($validations['maxDependentOn'])) {
                        $maxDependentFieldName = $validations['maxDependentOn'];

                        if (!empty($validations['maxDependentOn'])) {
                            $maxDependentFieldArr = explode('_', $validations['maxDependentOn']);
                            $maxDependentFieldName = (!empty($maxDependentFieldArr) && sizeof($maxDependentFieldArr) == 2) ? $maxDependentFieldArr[1] : $maxDependentFieldName;
                        }
                        $maxDependentValue = $data[$maxDependentFieldName] ?? null;

                        if ((!empty($data['from']) && !empty($maxDependentValue))) {
                            $maxDependentCarbonObj = Carbon::parse($maxDependentValue);
                            $valueCarbonObj = Carbon::parse($data['from']);

                            if ($valueCarbonObj->gt($maxDependentCarbonObj)) {
                                return $this->error(400, Lang::get('employeeMessages.basic.ERR_FROM_DATE_MUST_LESS'), []);
                            }
                        }
                    }
                }
            }

            $existingRecord = $this->store->getById($multiRecordModel, $multirecordId);
            if (empty($existingRecord)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if ($workflow) {

                // this is the workflow context id related for Profile Update
                $context = 1;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $id);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;

                $data['id'] = $multirecordId;
                $data['tabName'] = $multirecordAttribute;
                $data['isMultiRecord'] = true;
                $data['modelName'] = $attribute['modelName'];
                $data['relateAction'] = 'update';


                $finalStates = [];

                $relateRequestCount = $this->checkWhetherHasInprogressWorkflowRequests($id, $data, $finalStates, $context);

                if ($relateRequestCount > 0) {
                    DB::rollback();
                    return $this->error('500', Lang::get('employeeMessages.basic.ERR_HAS_REMAINING_INPROGRESS_WF'), $workflowDefineId);
                }


                //send this time change request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $data, $id);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                DB::commit();
                return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GO_WF'), $data);
            }
            $response = $this->store->updateById($multiRecordModel, $multirecordId, $data);
            $this->updateEmployeeRecordUpdatedAtColumn($id);
            $this->store->handleEffectiveDateConsiderableValues($this->employeeModel, $id, null, [$multirecordAttribute]);
            $this->handleEmployeeActiveStatus($id);
            DB::commit();
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_CREATE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_CREATE'), null);
        }
    }

    public function deleteEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId, $workflow = false)
    {
        try {
            DB::beginTransaction();
            $relation = $this->employeeModel->getRelationType($multirecordAttribute);
            if ($relation != RelationshipType::HAS_MANY) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET'), null);
            }

            $attribute = $this->employeeModel->getAttribute($multirecordAttribute);
            if (isset($attribute['modelName']) && $attribute['type'] != 'model') {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET'), null);
            }

            $multiRecordModel = $this->getModel($attribute['modelName'], true);

            $existingRecord = $this->store->getById($multiRecordModel, $multirecordId);
            if (empty($existingRecord)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (!empty($attribute['validations']) && !empty($attribute['validations']['isRequired']) && $attribute['validations']['isRequired']) {
                $multiRecordCount = $this->store->getFacade()::table($attribute['modelName'])
                    ->where('employeeId', $id)
                    ->count();
                if ($multiRecordCount < 2) {
                    return $this->error(400, Lang::get('employeeMessages.basic.ERR_IS_REQUIRED'), null);
                }
            }

            if ($workflow) {

                // this is the workflow context id related for Profile Update
                $context = 1;
                $data = (array)$existingRecord;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $id);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;

                foreach ($data as $key => $value) {
                    if ($key != 'id' && $key != 'createdAt' && $key != 'createdBy' && $key != 'updatedAt' && $key != 'updatedBy') {
                        $data[$key] = '-';
                    }
                }

                // $workflowDefineId = $workFlowId->id;
                $data['id'] = $multirecordId;
                $data['tabName'] = $multirecordAttribute;
                $data['isMultiRecord'] = true;
                $data['modelName'] = $attribute['modelName'];
                $data['relateAction'] = 'delete';
                //send this time change request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $data, $id);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }

                DB::commit();
                return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GO_WF'), $data);
            }

            $response = $this->store->deleteById($multiRecordModel, $multirecordId);
            $this->updateEmployeeRecordUpdatedAtColumn($id);
            $this->store->handleEffectiveDateConsiderableValues($this->employeeModel, $id, null, [$multirecordAttribute]);
            $this->handleEmployeeActiveStatus($id);
            DB::commit();
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_DELETE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_DELETE'), null);
        }
    }


    public function getEmployeeMultiRecord($multirecordAttribute, $multirecordId)
    {
        try {
            $relation = $this->employeeModel->getRelationType($multirecordAttribute);
            if ($relation != RelationshipType::HAS_MANY) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_EMP_MULTI'), null);
            }

            $attribute = $this->employeeModel->getAttribute($multirecordAttribute);
            if (isset($attribute['modelName']) && $attribute['type'] != 'model') {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_EMP_MULTI'), null);
            }

            $multiRecordModel = $this->getModel($attribute['modelName'], true);
            $modelFields = $multiRecordModel->getAttributes();

            $existingRecord = $this->store->getById($multiRecordModel, $multirecordId);
            if (empty($existingRecord)) {
                $existingRecord = [];
                return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_EMP_MULTI'), $existingRecord);
            }
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_EMP_MULTI'), $existingRecord);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_EMP_MULTI'), null);
        }
    }

    public function getMyProfileRelationalData($relationModels)
    {
        try {
            $results = [];
            foreach ($relationModels as $key => $value) {
                $columns = ['id'];

                if ($key == 'calendar') {
                    $dataSet = DB::table('workCalendar')->get();
                } else {

                    if (!is_null($value)) {
                        if ($key != 'fileStoreObject') {
                            array_push($columns, $value);
                            $model = $this->getModel($key, true);
                            if ($key == 'employee') {
                                array_push($columns, 'lastName');
                            }
                            $dataSet = $this->store->getAll($model, $columns);
                        } else {
                            $dataSet = DB::table('fileStoreObject')->get();
                        }
                    } else {
                        $dataSet = [];
                    }
                }
                $results[$key] = $dataSet;
            }
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_EMP_RELATION_DATA'), json_encode($results));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            error_log($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_RELATION_DATA'), null);
        }
    }


    public function getProfileUpdateDataDiff($request)
    {
        try {
            $employeeId = $request->query('employeeId', null);
            $workflowInstanceId = $request->query('workflowInstanceId', null);
            $modalName = $request->query('modalName', null);
            $scope = $request->query('scope', null);
            $pageType = $request->query('pageType', null);

            $queryBuilder = $this->store->getFacade();
            $workflowData = $queryBuilder::table('workflowInstance')
                ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
                ->where('workflowInstance.id', $workflowInstanceId)
                ->first();

            $workflowData = (array) $workflowData;

            if (is_null($workflowData)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }
            $newData = json_decode($workflowData['details']);
            $newData = (array) $newData;

            $currentData = [];

            if (!$newData['isMultiRecord']) {

                if ($pageType == 'allRequests') {
                    $currentData = $this->getEmployee($employeeId);
                    $currentData = (array) $currentData['data'];
                } else {
                    $currentData = $this->getEmployee($employeeId);
                    $currentData = (array) $currentData['data'];
                }
            } else {

                $id = (!empty($newData['id'])) ? $newData['id'] : 'new';
                $currentData = $this->getEmployeeMultiRecord($newData['tabName'], $id);
                $currentData = (array) $currentData['data'];
                if (sizeof($currentData)  == 0) {
                    //set object for create object
                    foreach ($newData as $field => $val) {
                        $currentData[$field] = '-';
                    }
                }
            }

            $model = $this->getModel($modalName, true);

            //get model wise field level permissions
            $fieldPermissions = $this->session->getPermission()->readableFields($modalName);
            $fieldPermissions = (array)$fieldPermissions;
            $fieldPermissions = array_values($fieldPermissions);


            $relateModel = $this->modelService->getModelByName($modalName, 'edit', true);
            $relateModel = json_decode($relateModel['data']);

            $fields = $model->getAttributes();
            $attributes = $model->getAttributesWithActualColumnsInTable();
            $relationArray = $this->getRelationsFromModel($relateModel);

            $relationalData = $this->getMyProfileRelationalData($relationArray);
            $relationalData = json_decode($relationalData['data']);
            $relationalData  = (array) $relationalData;
            $modelDefinitionFields = (array) $relateModel->modelDataDefinition->fields;

            $fieldArr = [];

            $differnce = $this->getObjectDiffernce($newData, $currentData, $fields);
            $index = 0;

            foreach ($modelDefinitionFields as $key => $value) {
                $value = (array) $value;
                $name = (empty($value['modelName'])) ? null : $key;
                $modelName = (empty($value['modelName'])) ? null : $value['modelName'];

                $systemValue = (!empty($value['isSystemValue'])) ? true : false;


                if ($value['type'] == 'model') {
                    $modelName = $value['modelName'];
                }

                $relations = (!empty($relateModel->modelDataDefinition->relations)) ? (array)$relateModel->modelDataDefinition->relations : [];
                $columnName = array_key_exists($key, $attributes) ? $attributes[$key] : $key;

                if (!$newData['isMultiRecord']) {
                    $curVal = (!empty($differnce[$columnName]['currentVal']) && $differnce[$columnName]['currentVal'] != 'noData') ? $differnce[$columnName]['currentVal'] : '-';
                    $newVal = (!empty($differnce[$columnName]['newVal']) && $differnce[$columnName]['newVal'] != 'noData') ? $differnce[$columnName]['newVal'] : '-';
                } else {
                    $curVal = (!empty($currentData[$columnName]) && $currentData[$columnName] != 'noData') ? $currentData[$columnName] : '-';
                    $newVal = (!empty($newData[$columnName]) && $newData[$columnName] != 'noData') ? $newData[$columnName] : '-';
                    if (!isset($newData[$columnName])) {
                        $newVal = $curVal;
                    }
                }

                if (!is_null($name) && !is_null($modelName)) {
                    $action = (!empty($relations[$name])) ? $relations[$name] : null;

                    if (!is_null($action) && $action !== 'HAS_MANY' && !$systemValue) {

                        if (sizeof($relationalData[$modelName]) > 0) {
                            foreach ($relationalData[$modelName] as $key1 => $value1) {
                                $value1 = (array) $value1;
                                if ($value1['id'] == $newVal) {

                                    if ($modelName == 'employee') {
                                        $newVal = $value1['firstName'] . ' ' . $value1['lastName'];
                                    } else {
                                        $newVal = $value1[$relationArray[$modelName]];
                                    }
                                }

                                if ($value1['id'] == $curVal) {
                                    if ($modelName == 'employee') {
                                        $curVal = $value1['firstName'] . ' ' . $value1['lastName'];
                                    } else {
                                        $curVal = $value1[$relationArray[$modelName]];
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($value)  && ($key != 'id' && $key != 'employeeId' && $key != 'createdAt' && $key != 'createdBy' && $key != 'updatedAt' && $key != 'updatedBy') && !$systemValue && ($fieldPermissions[0] == '*' || in_array($columnName, $fieldPermissions))) {

                    if (!$newData['isMultiRecord'] && $curVal != $newVal) {

                        $fieldArr[] = [
                            'key' => $index,
                            'field' =>  $value['defaultLabel'],
                            'type' => $value['type'],
                            'currentVal' => $curVal,
                            'newVal' => $newVal,
                            'fieldName' => $key,
                            'fieldSubName' => $key,

                        ];
                    } elseif ($newData['isMultiRecord']) {
                        $fieldArr[] = [
                            'key' => $index,
                            'field' =>  $value['defaultLabel'],
                            'type' => $value['type'],
                            'currentVal' => $curVal,
                            'newVal' => $newVal,
                            'fieldName' => $key,
                            'fieldSubName' => $key,

                        ];
                    }
                }

                $index += 1;
            }

            foreach ($fieldArr as $diffKey => $diffField) {
                if ($diffField['type'] == 'timestamp') {
                    if (!empty($diffField['currentVal']) && $diffField['currentVal'] !== '-') {
                        $fieldArr[$diffKey]['currentVal'] = Carbon::parse($diffField['currentVal'])->format('d-m-Y');
                    }

                    if (!empty($diffField['newVal']) && $diffField['newVal'] !== '-') {
                        $fieldArr[$diffKey]['newVal'] = Carbon::parse($diffField['newVal'])->format('d-m-Y');
                    }
                }

                if ($diffField['fieldName'] == 'bloodGroup') {
                }
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_EMP_PROFILE_UPDATE_DIFF'), $fieldArr);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            error_log($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_EMP_PROFILE_UPDATE_DIFF'), null);
        }
    }

    private function getObjectDiffernce($newData, $currentData, $fields)
    {
        $differnce = [];
        $newData = (array) $newData;
        $currentData = (array) $currentData;


        foreach ($fields as $key => $value) {
            $newValue = (array_key_exists($value, $newData)) ? $newData[$value] : 'noData';
            $currentValue = (!empty($currentData[$value])) ? $currentData[$value] : 'noData';

            $newValue = (!empty($newValue)) ? $newValue : '-';

            if ($newValue != 'noData'  && $newValue != $currentValue) {
                $differnce[$value] = [
                    'newVal' => $newValue,
                    'currentVal' => $currentValue
                ];
            }
        }

        return $differnce;
    }

    private function getRelationsFromModel($relateModel)
    {
        $arr = [];

        foreach ($relateModel->modelDataDefinition->fields as $key => $value) {
            $value = (array) $value;
            $name = (empty($value['modelName'])) ? null : $key;
            $modelName = (empty($value['modelName'])) ? null : $value['modelName'];

            $systemValue = (!empty($value['isSystemValue'])) ? true : false;


            if ($value['type'] == 'model') {
                $modelName = $value['modelName'];
            }

            $relations = (!empty($relateModel->modelDataDefinition->relations)) ? (array)$relateModel->modelDataDefinition->relations : [];


            if (!is_null($name) && !is_null($modelName)) {

                $action = (!empty($relations[$name])) ? $relations[$name] : null;

                if (!is_null($action) && $action !== 'HAS_MANY' && !$systemValue) {
                    if (!array_key_exists($modelName, $arr)) {
                        if ($modelName == 'employee') {
                            $arr[$modelName] = 'firstName';
                        } elseif ($modelName == 'user') {
                            $arr[$modelName] = 'firstName';
                        } else {
                            $arr[$modelName] = 'name';
                        }
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * Following function retrives current employement details of an employee .
     *
     * @param $id job id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */

    public function getCurrentJob($id)
    {
        try {
            //to do dynamically get current job of employee
            $queryBuilder = DB::table('employeeJob')
                ->select(
                    'department.name as departmentName',
                    'division.name as divisionName',
                    'jobTitle.name as jobTitle',
                    'location.name as locationName'
                )
                ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
                ->leftJoin('division', 'division.id', '=', 'employeeJob.divisionId')
                ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
                ->leftJoin('location', 'location.id', '=', 'employeeJob.locationId')
                ->where('employeeJob.id', '=', $id);

            $result = $queryBuilder->first();
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALLJOB'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeMessages.basic.ERR_GETALLJOB'), null);
        }
    }

    /**
     * Following function retrives a single employee from the logged in session.
     *
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getMyProfile()
    {
        try {
            $employeeId = $this->session->user->employeeId;
            if (!$employeeId) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }
            return $this->getEmployee($employeeId);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET'), null);
        }
    }

    public function getEmployeeProfilePicture($id)
    {
        try {
            $employee = $this->getEmployee($id);

            if ($employee['error']) {
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_PROFILE_PICTURE'), null);
            }

            if (empty($employee['data']->profilePicture)) {
                return $this->error(200, Lang::get('employeeMessages.basic.SUCC_PROFILE_PICTURE_NOT_UPLOADED'), null);
            }

            $response = [];
            $fileId = $employee['data']->profilePicture;

            if (!empty($fileId)) {
                $response = $this->fileStorage->getBase64EncodedObject($fileId);
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_PROFILE_PICTURE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_PROFILE_PICTURE'), null);
        }
    }

    public function storeEmployeeProfilePicture($id, $data, $workflow = false)
    {
        try {
            $file = $this->fileStorage->putBase64EncodedObject(
                $data['fileName'],
                $data['fileSize'],
                $data["data"]
            );

            $existingRecord = $this->store->getById($this->employeeModel, $id);
            if (empty($existingRecord)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }
            $existingRecord = (array)$existingRecord;

            $existingRecord['profilePicture'] = $file->id;
            $existingRecord['isOTAllowed'] = $existingRecord['isOTAllowed'] == 1 ? true : false;
            $result = $this->updateEmployee($id, $existingRecord, $workflow);
            if ($result['error']) {
                Log::error(json_encode($result));
                return $this->error(500, $result['message'], $result['data']);
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_STORE_PROFILE_PICTURE'), $file);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_STORE_PROFILE_PICTURE'), null);
        }
    }

    public function removeEmployeeProfilePicture($id)
    {
        try {
            $employee = $this->getEmployee($id);

            if ($employee['error'] || !isset($employee['data']->profilePicture)) {
                Log::error(json_encode($employee));
                return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_PROFILE_PICTURE'), null);
            }

            $response = null;
            $fileId = $employee['data']->profilePicture;

            if (!empty($fileId)) {
                $response = $this->fileStorage->deleteObject($fileId);

                $result = $this->updateEmployee($id, ['profilePicture' => null]);
                if ($result['error']) {
                    Log::error(json_encode($result));
                    return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_PROFILE_PICTURE'), null);
                }
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_PROFILE_PICTURE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_PROFILE_PICTURE'), null);
        }
    }

    /**
     * Following function retrives all managers.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All managers retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllManagers($permittedFields, $options)
    {
        try {
            $employees = $this->store->getFacade()::table($this->employeeModel->getName())
                ->join(
                    $this->userModel->getName(),
                    $this->userModel->getName() . ".employeeId",
                    '=',
                    $this->employeeModel->getName() . ".id"
                )
                ->whereNotNull($this->userModel->getName() . ".managerRoleId")
                ->where($this->employeeModel->getName() . ".isDelete", false)
                ->select(
                    $this->employeeModel->getName() . ".id",
                    'employee.employeeNumber',
                    DB::raw(
                        "CONCAT_WS(' ', "
                            . $this->employeeModel->getName() . ".firstName, "
                            . $this->employeeModel->getName() . ".middleName, "
                            . $this->employeeModel->getName() . ".lastName) AS employeeName"
                    )
                )
                ->get();

            $employees = array_values(
                array_map(
                    "unserialize",
                    array_unique(
                        array_map("serialize", collect($employees)->toArray())
                    )
                )
            );

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }

        // try {
        //     $employees = $this->store->getFacade()::table($this->employeeModel->getName())
        //         ->where("isDelete", false)
        //         ->select("id", DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName"))
        //         ->get()
        //         ->toArray();
        //     return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        // } catch (Exception $e) {
        //     Log::error($e->getMessage());
        //     return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        // }
    }

    /**
     * Following function retrives workflow permitted all managers.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All managers retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllWorkflowPermittedManagers($workflowId)
    {
        try {
            if (empty($workflowId)) {
                return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
            }

            //get related workflow context
            $workflowRecord = $this->store->getById($this->workflowModel, $workflowId);
            if (empty($workflowRecord)) {
                return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
            }

            $contextId = 'workflow-' . $workflowRecord->contextId;

            $employees = $this->store->getFacade()::table('employee')
                ->join('user', 'user.employeeId', '=', 'employee.id')
                ->where('employee.isDelete', false)
                ->where(function ($query) {
                    $query->whereNotNull('user.managerRoleId');
                    $query->orwhereNotNull('user.adminRoleId');
                })
                ->select(
                    'employee.id',
                    'employee.employeeNumber',
                    'user.managerRoleId',
                    'user.adminRoleId',
                    DB::raw(
                        "CONCAT_WS(' ', "
                            . "employee.firstName, "
                            . "employee.middleName, "
                            . "employee.lastName) AS employeeName"
                    )
                )->get();

            $employeeDataSet = [];
            foreach ($employees as $key => $value) {
                $value = (array) $value;
                $isAddToArray = false;

                if (!empty($value->managerRoleId)) {
                    $managerRoleData = $this->store->getFacade()::table('userRole')->where('userRole.id', $value->managerRoleId)->select(
                        'id',
                        'workflowManagementActions',
                    )->first();


                    $workflowManagementActions =  json_decode($managerRoleData->workflowManagementActions);
                    if (!is_null($managerRoleData) && !empty($workflowManagementActions)) {

                        if (in_array('*', $workflowManagementActions)) {
                            $isAddToArray = true;
                            $employeeDataSet[] = $value;
                        } elseif (in_array($contextId, $workflowManagementActions)) {
                            $employeeDataSet[] = $value;
                            $isAddToArray = true;
                        }
                    }
                }

                if (!empty($value->adminRoleId) && !$isAddToArray) {
                    $adminRoleData = $this->store->getFacade()::table('userRole')->where('userRole.id', $value->adminRoleId)->select(
                        'id',
                        'workflowManagementActions',
                    )->first();

                    $workflowManagementActions =  json_decode($adminRoleData->workflowManagementActions);
                    if (!is_null($adminRoleData) && !empty($workflowManagementActions)) {
                        if (in_array('*', $workflowManagementActions)) {
                            $employeeDataSet[] = $value;
                        } elseif (in_array($contextId, $workflowManagementActions)) {
                            $employeeDataSet[] = $value;
                        }
                    }
                }
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employeeDataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives workflow permitted all managers.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All managers retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllWorkflowPermittedManagersAndAdmins()
    {
        try {

            $employees = $this->store->getFacade()::table('employee')
                ->join('user', 'user.employeeId', '=', 'employee.id')
                // ->join('userRole', 'userRole.id', '=', 'user.managerRoleId')
                ->where('employee.isDelete', false)
                ->where(function ($query) {
                    $query->whereNotNull('user.managerRoleId');
                    $query->orwhereNotNull('user.adminRoleId');
                })
                ->select(
                    'employee.id',
                    // 'userRole.workflowManagementActions',
                    'employee.employeeNumber',
                    'user.managerRoleId',
                    'user.adminRoleId',
                    DB::raw(
                        "CONCAT_WS(' ', "
                            . "employee.firstName, "
                            . "employee.middleName, "
                            . "employee.lastName) AS employeeName"
                    )
                )->get();

            $employeeDataSet = [];
            foreach ($employees as $key => $value) {
                $allowToAdd = false;
                if (!empty($value->managerRoleId)) {
                    $managerRoleData = $this->store->getFacade()::table('userRole')->where('userRole.id', $value->managerRoleId)->select(
                        'id',
                        'workflowManagementActions',
                    )->first();

                    if (!is_null($managerRoleData) && !empty(json_decode($managerRoleData->workflowManagementActions))) {
                        $allowToAdd = true;
                    }
                }

                if (!empty($value->adminRoleId)) {
                    $adminRoleData = $this->store->getFacade()::table('userRole')->where('userRole.id', $value->adminRoleId)->select(
                        'id',
                        'workflowManagementActions',
                    )->first();

                    if (!is_null($adminRoleData) && !empty(json_decode($adminRoleData->workflowManagementActions))) {
                        $allowToAdd = true;
                    }
                }

                if ($allowToAdd) {
                    $employeeDataSet[] = $value;
                }
            }

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employeeDataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    public function getEmployeeOrgChart()
    {
        try {

            //get RootEmployeeId from company table
            $company = DB::table('company')->first('rootEmployeeId');

            $employeeIds =  $this->store->getFacade()::table($this->employeeModel->getName())
                ->leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->whereNull('employeeJob.reportsToEmployeeId')
                ->where('employee.id', '!=', $company->rootEmployeeId)
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->pluck('employee.id')->toArray();

            $employeeData =  $this->store->getFacade()::table($this->employeeModel->getName())
                ->Leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->Leftjoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
                ->selectRaw('employee.id AS EmployeeID, employee.firstName AS employeeFirstName , employee.lastName AS employeeLastName , employee.imageUrl as imageUrl, employee.profilePicture')
                ->selectRaw('jobTitle.name AS employeeDesignation')
                ->selectRaw('employeeJob.reportsToEmployeeId AS parentId')
                ->whereNotIn('employee.id', $employeeIds)
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->get();


            foreach ($employeeData as $key => $value) {
                error_log(json_encode($value->profilePicture));
                if (!empty($value->profilePicture)) {
                    $profilePic = $this->fileStorage->getBase64EncodedObject($value->profilePicture);
                    $employeeData[$key]->imageUrl = $profilePic->data;
                }
            }

            $employeeOrgChartData = $this->generateEmployeeChart($employeeData, null);

            if (is_null($employeeOrgChartData)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NULL_GET_EMP_CHART'), null);
            }

            if (empty($employeeOrgChartData)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NO_EMP_CHART'), null);
            }

            $orgChartData =  empty($employeeOrgChartData) ? [] : $employeeOrgChartData[0];

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_EMP_CHART'), $orgChartData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_EMP_CHART'), null);
        }
    }

    private function generateEmployeeChart($elements, $parentId = null)
    {
        $branch = array();
        $colors = ['#00AA55', '#009FD4', '#B381B3', '#939393', '#E3BC00', '#D47500', '#DC2A2A'];
        foreach ($elements as $element) {

            $element->collapsed = null;
            $element->color = $colors[rand(0, 6)];
            if ($element->parentId == $parentId) {
                $children = $this->generateEmployeeChart($elements, $element->EmployeeID);
                if ($children) {
                    $element->organizationChildRelationship = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * Following function retrives permitted employees details for particular user .
     *
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */

    public function getPermitedEmployeesForUser($sortBy = false)
    {
        try {
            $queryBuilder = DB::table('employee')
                ->select(
                    'id',
                    'employeeNumber',
                    'firstName',
                    'lastName'
                )
                ->whereIn('id', $this->session->getContext()->getPermittedEmployeeIds());

            if ($sortBy) {
                $queryBuilder->orderBy('employee.firstName', 'ASC');
            }

            $result = $queryBuilder->get();
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GET_PERMITED_EMP'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeMessages.basic.ERR_GET_PERMITED_EMP'), null);
        }
    }

    /**
     * Following function update a employee through workflow.
     *
     * @param $id user employee id
     * @param $employee array containing employee data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateMyProfilePersonalRecords($id, $employee)
    {
        try {
            $isFromWorkflow = true;
            $validationResponse = ModelValidator::validate($this->employeeModel, $employee, true);

            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            $existingEmployee = $this->store->getById($this->employeeModel, $id);
            if (empty($existingEmployee)) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if ($isFromWorkflow) {
                $context = 1;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $id);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;

                //send this profile update request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $employee, $id);

                $response = $this->getEmployee($id);
                return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GO_THROUGH_WF'), $response['data']);
            }

            $response = $this->store->updateById($this->employeeModel, $id, $employee);

            if (!$response) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_UPDATE'), $id);
            }

            $this->handleEmployeeActiveStatus($id);
            $response = $this->getEmployee($id);
            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_UPDATE'), $response['data']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_UPDATE'), null);
        }
    }

    public function getEmployeeList($scope)
    {
        try {
            $queryBuilder = DB::table('employee')->select(
                'id',
                'employeeNumber',
                DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
            );

            if (!is_null($scope)) {
                $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
                $queryBuilder = $queryBuilder->whereIn('id', $permittedEmployeeIds);
            }

            $employees = $queryBuilder->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->orderBy('employee.firstName', 'ASC')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    public function getAllEmployeeList($scope)
    {
        try {
            $queryBuilder = DB::table('employee')->select(
                'id',
                'employeeNumber',
                DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName, isActive")
            );

            if (!is_null($scope)) {
                $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
                $queryBuilder = $queryBuilder->whereIn('id', $permittedEmployeeIds);
            }

            $employees = $queryBuilder->where('employee.isDelete', '=', false)
                ->orderBy('employee.firstName', 'ASC')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }


    public function getEmployeeListByEntityId($scope, $entityId)
    {
        try {
            $queryBuilder = DB::table('employee')->select(
                'employee.id',
                'employee.employeeNumber',
                DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
            )->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId');

            if (!is_null($scope)) {
                $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
                $queryBuilder = $queryBuilder->whereIn('employee.id', $permittedEmployeeIds);

                if ($entityId) {
                    $entityIds = $this->getParentEntityRelatedChildNodes((int)$entityId);
                    array_push($entityIds, (int)$entityId);

                    $queryBuilder = $queryBuilder->whereIn('employeeJob.orgStructureEntityId', $entityIds);
                }
            }

            $employees = $queryBuilder->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->orderBy('employee.firstName', 'ASC')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
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


    private function checkWhetherHasInprogressWorkflowRequests($employeeId, $draftData, $finalStates, $context)
    {

        $workFlowDetails = DB::table('workflowInstance')
            ->select('workflowInstance.*', 'workflowDetail.employeeId', 'workflowDetail.details')
            ->leftJoin('workflowDefine', 'workflowDefine.id', '=', 'workflowInstance.workflowId')
            ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
            ->where('workflowDefine.contextId', '=', $context)
            ->where('workflowDetail.employeeId', '=', $employeeId)
            ->where('workflowInstance.isDelete', '=', false)
            ->whereNotIn('workflowInstance.currentStateId', [2, 3, 4])
            ->get();

        $relatedDocumentCount = 0;

        if (sizeof($workFlowDetails) > 0) {
            foreach ($workFlowDetails as $key => $value) {
                $value = (array) $value;
                $details = json_decode($value['details']);
                $details = (array) $details;


                if ($details['relateAction'] == 'update' && $draftData['tabName'] == $details['tabName'] && $draftData['id'] == $details['id']) {
                    $relatedDocumentCount += 1;
                }
            }
        }

        return $relatedDocumentCount;
    }

    /**
     * Following function retrives employees for a provided where clauses.
     *
     * @param @ id
     * @return int | String | array
     *
     * Usage:
     * $departmentId => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "employee loaded Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getEmployeesByKeyword($options)
    {
        try {
            $employees = $this->store->getFacade()::table('employee')
                ->leftJoin('employeeJob', 'employee.currentJobsId', '=', 'employeeJob.id')
                ->where([['employeeJob.departmentId', '=', $options["departmentId"]], ['employeeJob.locationId', '=', $options["locationId"]]])
                ->where('isDelete', false)
                // ->whereIn('id', $this->session->getContext()->getPermittedEmployeeIds())
                ->get();


            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives all employees.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All employees retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllEmployeesFiltered($permittedFields, $options)
    {
        try {
            // get permitted employee ids
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
            $customWhereClauses = ['whereIn' => ['id' => $permittedEmployeeIds]];

            $employees = $this->store->getAll(
                $this->employeeModel,
                $permittedFields,
                $options,
                ['gender', 'currentJobs'],
                $customWhereClauses
            );

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives employee side card details.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee details retrived Successuflly!",
     *      $data => ["title": "LK HR", ...]
     * ]
     */
    public function getEmployeeSideCardDetails($id)
    {
        try {
            $employee = $this->store->getFacade()::table('employee')
                ->leftJoin('employeeJob', 'employee.currentJobsId', '=', 'employeeJob.id')
                ->leftJoin('jobTitle', 'employeeJob.jobTitleId', '=', 'jobTitle.id')
                ->leftJoin('location', 'employeeJob.locationId', '=', 'location.id')
                ->leftJoin('employee AS manager', 'employeeJob.reportsToEmployeeId', '=', 'manager.id')
                ->leftJoin('employeeJob AS managerJob', 'manager.currentJobsId', '=', 'managerJob.id')
                ->leftJoin('jobTitle AS managerJobTitle', 'managerJob.jobTitleId', '=', 'managerJobTitle.id')
                ->where('employee.id', $id)
                ->select([
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                    "jobTitle.name AS currentJobTitle",
                    "employee.employeeNumber AS employeeNumber",
                    "employee.profilePicture As profilePictureId",
                    "employee.workEmail AS workEmail",
                    "employee.mobilePhone AS mobilePhone",
                    "location.name AS currentLocation",
                    DB::raw("CONCAT_WS(' ', manager.firstName, manager.middleName, manager.lastName) AS reportingPerson"),
                    "managerJobTitle.name AS reportingPersonJobTitle",
                ])
                ->first();

            $profilePicture = json_decode(json_encode($this->getEmployeeProfilePicture($id)), true);
            $employee->profilePicture = !empty($profilePicture)
                && !empty($profilePicture['data'])
                && !empty($profilePicture['data']['data'])
                ? $profilePicture['data']['data'] : null;

            $company = (array) $this->session->getCompany();
            $companyTimeZone = new DateTimeZone($company["timeZone"] ?? null);
            $today = new DateTime("now", $companyTimeZone);
            $employeeWorkShift = null;

            $shift = $this->getEmployeeWorkShift($id, Carbon::now());

            if (!is_null($shift) && !empty($shift->workShiftName)) {
                $employeeWorkShift = $shift->workShiftName;
            }

            $isRelatedToWorkPattern = false;
            $employeePattern = null;

            if (isset($shift->workPatternId)) {
                $isRelatedToWorkPattern = true;
                if (!is_null($shift->workPatternId)) {

                    $employeePattern = $this->store->getFacade()::table('workPattern')
                        ->where('id', $shift->workPatternId)
                        ->select('workPattern.name AS workPatternName')->first();
                }
            }


            // if (empty($employeePattern)) {
            //     $empLocation = $this->store->getFacade()::table('employeeJob')
            //         ->where('effectiveDate', '<=', Carbon::parse($today)->format('Y-m-d'))
            //         ->where('employeeId', $id)
            //         ->orderBy('id', 'DESC')->first();
            //     if (!empty($empLocation)) {
            //         $employeePattern = $this->store->getFacade()::table('workPatternLocation')
            //             ->select('workPattern.name AS workPatternName')
            //             ->leftJoin('workPattern', 'workPattern.id', '=', 'workPatternLocation.workPatternId')
            //             ->leftJoin('workPatternWeek', 'workPatternWeek.workPatternId', '=', 'workPattern.id')
            //             ->leftJoin('workPatternWeekDay', 'workPatternWeekDay.workPatternWeekId', '=', 'workPatternWeek.id')
            //             ->leftJoin('dayOfWeek', 'dayOfWeek.id', '=', 'workPatternWeekDay.dayTypeId')
            //             ->leftJoin('workShifts', 'workShifts.id', '=', 'workPatternWeekDay.workShiftId')
            //             ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
            //             ->where(
            //                 function ($query) {
            //                     return $query
            //                         ->where('workShiftDayType.startTime', '<>', NULL)
            //                         ->where('workShiftDayType.endTime', '<>', NULL);
            //                 }
            //             )
            //             ->where('dayOfWeek.dayName', '=', strtolower($today->format('l')))
            //             ->where('locationId', $empLocation->locationId)
            //             ->first();
            //     }
            // }

            $employee->workPattern = !empty($employeePattern) ? $employeePattern->workPatternName : null;
            $employee->workShift = !empty($employeeWorkShift) ? $employeeWorkShift : null;
            $employee->isRelatedToWorkPattern = $isRelatedToWorkPattern;

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employee);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /*
      get the list of Unassigned employees for the user

     * @param $data
     * @param $data array containing employeeId
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee loaded successfully",
     *      $data => [{"id": "1", fullName:'John Dave'} ....] , // has a similar set of data .
     *
    */

    public function getUnassignedEmployees($data)
    {
        try {

            $assignedEmployeeIds = DB::table('user')
                ->leftJoin('employee', 'employee.id', '=', 'user.employeeId')
                ->whereNotNull('user.employeeId');

            //exclude the selected employeeId from the $assignedEmployeeIds when editing the user
            if (isset($data['employeeId'])) {
                $assignedEmployeeIds->where('user.employeeId', '!=', $data['employeeId']);
            }
            $assignedEmpIds = $assignedEmployeeIds->pluck('employeeId');

            $unAssignedEmployeeIds = DB::table('employee')
                ->select(
                    'id',
                    'employeeNumber',
                    'workEmail',
                    'firstName',
                    'middleName',
                    'lastName',
                    DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
                )
                ->whereNotIn('id', $assignedEmpIds)
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true);

            $employeesList =  $unAssignedEmployeeIds->orderBy('employee.firstName', 'ASC')->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employeesList);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    private function checkEmployeeNumberFormat($payLoadId)
    {
        $getPrefixCode = DB::table('prefixCode')->where('modelType', 'employee')->first();

        if (empty($getPrefixCode)) {
            return $payLoadId;
        }

        $prefixStringLength = strlen($getPrefixCode->prefix);
        $prefixCode = $getPrefixCode->prefix;
        $prefixCodeLength = $getPrefixCode->length;
        $employeeNumberPrefixCode = substr($payLoadId, 0, $prefixStringLength);

        if (!strcmp($prefixCode, $employeeNumberPrefixCode)) {
            $employeeNumber = substr($payLoadId, $prefixStringLength);
            if (strlen($employeeNumber) ==  $prefixCodeLength) {
                return $payLoadId;
            }
        } else {
            return null;
        }
    }

    private function handleBackdatedLeaveAccrual($employee)
    {
        $company = (array) $this->session->getCompany();
        $companyTimeZone = !empty($company["timeZone"]) ? $company["timeZone"] : 'UTC';
        $tenantId = $this->session->getTenantId();
        $companyDateObject = Carbon::now($companyTimeZone);
        $hireDateObject = Carbon::parse($employee['hireDate']);

        // if hired date is back dated run back dated leave accrual job
        if (!is_null($tenantId) && $hireDateObject->lessThanOrEqualTo($companyDateObject)) {
            $data = [
                'tenantId' => $tenantId,
                'employeeId' => $employee['id'],
                'hireDate' => $employee['hireDate'],
                'currentDate' => $companyDateObject->format('Y-m-d')
            ];

            // for backdated leave accrual
            dispatch(new LeaveAccrualBackdatedJob($data));
        }
    }

    /**
     * Check whether new hire date is less than old
     * @param $oldEmployeeObject
     * @param $newEmployeeObject
     *
     * @return boolean
     */
    private function isHireDateUpdatedAndLessThanPrevious($oldEmployeeObject, $newEmployeeObject)
    {
        $newHireDateObj = isset($newEmployeeObject['hireDate']) ? Carbon::parse($newEmployeeObject['hireDate']) : null;
        $oldHireDateObj = isset($oldEmployeeObject['hireDate']) ? Carbon::parse($oldEmployeeObject['hireDate']) : null;

        if (is_null($newHireDateObj) || is_null($oldHireDateObj)) {
            return false;
        }

        return $newHireDateObj->lessThan($oldHireDateObj);
    }

    private function handleBackdatedAttendance($employee)
    {
        try {
            // get location timezone
            $jobLocationId = (isset($employee['jobs']) && count($employee['jobs']) > 0) ? $employee['jobs'][0]['locationId'] : null;

            if (is_null($jobLocationId) || empty($employee['hireDate'])) {
                return false;
            }

            $location = $this->store->getFacade()::table('location')->where('id', '=', $jobLocationId)->first(['id', 'timeZone']);

            if (empty($location)) {
                return false;
            }

            $tenantId = $this->session->getTenantId();
            $locationDateObject = Carbon::now($location->timeZone);
            $hireDateObject = Carbon::parse($employee['hireDate']);
            $period = CarbonPeriod::create($employee['hireDate'], $locationDateObject->format('Y-m-d'));

            $dates = [];

            foreach ($period as $dateObject) {
                $dates[] = $dateObject->format('Y-m-d');
            }

            // if hired date is back dated run back dated leave accrual job
            if (!is_null($tenantId) && $hireDateObject->lessThanOrEqualTo($locationDateObject)) {
                $data = [
                    'tenantId' => $tenantId,
                    'employeeId' => $employee['id'],
                    'dates' => $dates
                ];
                // for backdated leave accrual
                dispatch(new BackDatedAttendanceProcess($data));
            }
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    /* get all the subordinates of selected manager*/
    public function getSubordinatesForSelectedManager($id)
    {
        try {

            $employees =  DB::table('employee')
                ->select(
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                    'employee.id'
                )
                ->leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->where('employeeJob.reportsToEmployeeId', '=', $id)
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /*
      get the list of employees who doesn't have reporting person

     * @param $data
     * @param $data array containing employeeId
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee loaded successfully",
     *      $data => [{"id": "1", fullName:'John Dave'} ....] , // has a similar set of data .
     *
    */
    public function getRootEmployees()
    {
        try {

            $rootEmployeesList = DB::table('employee')
                ->select(
                    'employee.id',
                    'employee.employeeNumber',
                    'firstName',
                    'middleName',
                    'lastName',
                    DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
                )
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->whereNull('employeeJob.reportsToEmployeeId')
                ->orderBy('employeeName')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $rootEmployeesList);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /* get employees for selected Department */

    public function getEmployeesForDepartmentAndlocation($data)
    {
        try {
            $employees =  DB::table('employee')
                ->select(
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                    'employee.id'
                )
                ->leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->where('departmentId', $data['departmentId'])
                ->where('locationId', $data['locationId'])
                ->get();



            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /* get employees for selected department
     * @param $departmentId
     * @param  containing departmentId
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee loaded successfully",
     *      $data => [{"id": "1", fullName:'John Dave'} ....] ,
    */
    public function getEmployeesForDepartment($departmentId)
    {
        try {
            $employeesList =  $this->store->getFacade()::table('employee')
                ->select(
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                    'employee.id'
                )
                ->leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employeeJob.departmentId', $departmentId)
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'),  $employeesList);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /* get employees for selected location

     * @param $locationId
     * @param  containing locationId
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee loaded successfully",
     *      $data => [{"id": "1", fullName:'John Dave'} ....] ,
    */

    public function getEmployeesForLocation($locationId)
    {
        try {
            $employeesList =  $this->store->getFacade()::table('employee')
                ->select(
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                    'employee.id'
                )
                ->leftjoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employeeJob.locationId', $locationId)
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'),  $employeesList);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }


    /* get employee by employee number

     * @param $employeeNumber
     * @return | String |
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employee retrive successfully",
     *      $data => ["id": "1", fullName:'John Dave'....] ,
    */

    public function getEmployeeByEmployeeNumber($employeeNumber)
    {
        try {

            $employee =  $this->store->getFacade()::table('employee')
                ->select(
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                    'employee.id'
                )
                ->where('employeeNumber', '=', $employeeNumber)
                ->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'),  $employee);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    public function getEmpProfilePicture($id)
    {
        try {

            $employee = DB::table('employee')->where('id', $id)->select('profilePicture')->first();

            $response = [];
            $imageFile = [];
            if ($employee->profilePicture !== null) {
                $response = $this->fileStorage->getBase64EncodedObject($employee->profilePicture);
                $imageFile = $this->fileStorage->getImageForEncodedData($response);
            }
            return response($imageFile['data'])->header('Content-Type', $imageFile['contentType']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET_PROFILE_PICTURE'), null);
        }
    }

    /* get employees for leave covering person dropdown
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employees retrive successfully",
     *      $data => ["id": "1", fullName:'John Dave'....] ,
    */
    public function getEmployeeListForCoveringPerson()
    {
        try {

            $employeeId = $this->session->getUser()->employeeId;
            $queryBuilder = DB::table('employee')->select(
                'id',
                'employeeNumber',
                DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
            );

            $employees = $queryBuilder->where('employee.isDelete', '=', false)
                ->where('employee.id', '!=', $employeeId)
                ->where('employee.isActive', '=', true)
                ->orderBy('employee.firstName', 'ASC')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /* get employees for claim allocation
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Employees retrive successfully",
     *      $data => ["id": "1", fullName:'John Dave'....] ,
    */
    public function getEmployeeListForClaimAllocation($selectedClaimType, $selectedFinacialYear)
    {
        try {

            //get all employee ids that has record for selected financial year and claimType
            $hasRecordEmployees = DB::table('claimAllocationDetail')
                ->where('financialYearId', '=', $selectedFinacialYear)
                ->where('claimTypeId', '=', $selectedClaimType)
                ->groupBy("employeeId")
                ->pluck('employeeId')->toArray();

            //get all employee ids
            $allEmployees = DB::table('employee')->where('employee.isDelete', '=', false)
                ->where('employee.isActive', '=', true)
                ->whereNotIn('id', $hasRecordEmployees)
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $allEmployees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    public function employeeCreateValidation(&$employee, $hasRelationalObj = false)
    {
        $validationResponse = ModelValidator::validate($this->employeeModel, $employee, false, $hasRelationalObj);
        if (!empty($validationResponse)) {
            return $this->error(400, Lang::get('employeeMessages.basic.VALIDATOIN_ERR'), $validationResponse);
        }

        // $employeeNumber = $this->checkEmployeeNumberFormat($employee['employeeNumber']);
        // if (is_null($employeeNumber)) {
        //     return $this->error(400, Lang::get('employeeMessages.basic.INVALID_EMP_NUMBER_FORMAT'), null);
        // }

        // $employee['employeeNumber'] = $employeeNumber;
    }

    /**
     * Following function retrives a my profile of a employee without considering field level access.
     *
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Employee retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getMyProfileView()
    {
        try {
            $employeeId = $this->session->user->employeeId;
            if (!$employeeId) {
                return $this->error(404, Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->getEmployee($employeeId, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GET'), null);
        }
    }
}
