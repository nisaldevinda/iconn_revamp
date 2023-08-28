<?php

namespace App\Jobs;

use App\Library\ActiveDirectory;
use App\Library\AzureUser;
use App\Services\EmployeeService;
use App\Traits\ConfigHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Log;

class AzureUserExtractingJob extends AppJob
{
    use ConfigHelper;

    protected $data;
    private $LIMIT = 20;

    /**
     * Create a new AzureUserExtractingJob instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the AzureUserExtractingJob.
     *
     * @return void
     */
    public function handle(ActiveDirectory $activeDirectory, EmployeeService $employeeService, AzureUser $azureUser)
    {
        // set tenant connection
        $this->setConnection($this->data['tenantId']);

        try {
            $employeeImportJobId = $this->data['employeeImportJobId'];
            $employeeImportJob = DB::table('employeeImportJob')
                ->where('id', $employeeImportJobId)
                ->first();
            $fieldMap = json_decode($employeeImportJob->fieldMap, true);
            $selectedAzureProperty = array_column($fieldMap, 'azureKey');
            $selectedAzureProperty = array_merge(['id', 'accountEnabled'], $selectedAzureProperty);

            $activeDirectory->initClient(
                $this->getConfigValue('azure_tenant_id'),
                $this->getConfigValue('azure_client_id'),
                $this->getConfigValue('azure_client_secret')
            );

            $totalCount = 0;
            $updatedEmployeeList = [];
            $userCollection = $activeDirectory->getUserCollection($this->LIMIT, $selectedAzureProperty);

            DB::table('stagingEmployee')->where('status', '!=', 'SUCCESS')->delete();

            while (!$userCollection->isEnd()) {
                $pageUsers = $userCollection->getPage();

                $_pageUsers = [];
                $updatedUserCount = 0;

                foreach ($pageUsers as $user) {
                    $_user = [
                        'email' => $user->getUserPrincipalName(),
                        'employeeImportJobId' => $employeeImportJobId,
                        'sourceObjectId' => $user->getId(),
                        'sourceObject' => json_encode($user),
                        'status' => 'PENDING'
                    ];

                    $existingStagingEmployee = DB::table('stagingEmployee')
                        ->where('sourceObjectId', $_user['sourceObjectId'])
                        ->first();

                    if (!$existingStagingEmployee) {
                        $_pageUsers[] = $_user;
                        $updatedEmployeeList[] = $_user['sourceObjectId'];
                    } else if ($existingStagingEmployee->status == 'ERROR') {
                        DB::table('stagingEmployee')
                            ->where('sourceObjectId', $_user['sourceObjectId'])
                            ->update($_user);
                        $updatedEmployeeList[] = $_user['sourceObjectId'];
                        $updatedUserCount++;
                    }
                }

                DB::table('stagingEmployee')->insert($_pageUsers);

                $totalCount += (sizeof($_pageUsers) + $updatedUserCount);
                DB::table('employeeImportJob')
                    ->where('id', $employeeImportJobId)
                    ->update(['totalCount' => $totalCount]);
            }

            DB::table('employeeImportJob')->where('id', $employeeImportJobId)->update(['status' => 'PROCESSING']);

            $azureUsers = DB::table('stagingEmployee')
                ->select('sourceObjectId as userId', 'sourceObject->manager->id as managerId')
                ->where('status', 'PENDING')
                ->get()
                ->toArray();
            $orderedAzureUsers = $this->buildUserInsertingOrder($azureUsers);

            $validationError = false;

            Log::info($fieldMap);
            foreach ($orderedAzureUsers as $id) {
                $stagingEmployeeRecord = DB::table('stagingEmployee')->where('sourceObjectId', $id)->first();
                $transformedObject = $azureUser->toEmployee(json_decode($stagingEmployeeRecord->sourceObject, true));

                try {
                    $result = $employeeService->employeeCreateValidation($transformedObject, true);
                } catch (Exception $e) {
                    $result = [
                        'error' => true,
                        'message' => 'Unknown error'
                    ];
                    error_log('employeeCreateValidation > ' . $e->getMessage());
                }

                if (isset($result['error']) && $result['error']) {
                    $validationError = true;
                    DB::table('stagingEmployee')
                        ->where('sourceObjectId', $id)
                        ->update([
                            'status' => 'ERROR',
                            'responseData' => $result,
                            'transformedObject' => $transformedObject
                        ]);
                    continue;
                }

                DB::table('stagingEmployee')
                    ->where('sourceObjectId', $id)
                    ->update([
                        'transformedObject' => json_encode($transformedObject)
                    ]);
            }

            if (!$validationError) {
                dispatch(new StagingEmployeeLoadingJob([
                    'tenantId' => $this->data['tenantId'],
                    'employeeImportJobId' => $employeeImportJobId,
                    'stagingEmployeeList' => $orderedAzureUsers
                ]));
            }

            DB::table('employeeImportJob')->where('id', $employeeImportJobId)->update(['status' => 'SUCCESS']);
        } catch (Exception $e) {
            Log::error('Import azure user job error -> ' . json_encode($e->getMessage()));
            DB::table('employeeImportJob')->where('id', $employeeImportJobId)->update(['status' => 'ERROR']);
        }
    }

    private function createSystemEmployee($azureUser, $fieldMap)
    {
        $employeeFields = ['employeeNumber', 'firstName', 'lastName', 'fullName', 'hireDate', 'workEmail', 'mobilePhone', 'personalEmail', 'createdAt'];
        $mandatoryFields = ['employeeNumber', 'hireDate'];
        $errors = [];
        $info = [];
        $employee = [];

        foreach ($employeeFields as $employeeField) {
            $mapObjectIndex = array_search($employeeField, array_column($fieldMap, 'employeeFieldName'));
            $mapObject = $fieldMap[$mapObjectIndex];

            if (empty($mapObject) || empty($mapObject['azureKey']))
                continue;

            // employee creation related validation
            if (in_array($employeeField, $mandatoryFields) && empty($azureUser[$mapObject['azureKey']]))
                $errors[] = $mapObject['azureFieldTitle'] . ' is required';

            $employee[$employeeField] = $azureUser[$mapObject['azureKey']] ?? null;
        }

        $employee['workPhone'] = is_array($azureUser['businessPhones']) && !empty($azureUser['businessPhones'])
            ? $azureUser['businessPhones'][0] : null;

        return [
            'error' => $errors,
            'info' => $info,
            'data' => $employee
        ];
    }

    private function createSystemUser($azureUser, $fieldMap, $extraValues)
    {
        $userFields = ['workEmail', 'firstName', 'lastName'];
        $errors = [];
        $info = [];
        $user = [];

        foreach ($userFields as $userField) {
            $mapObjectIndex = array_search($userField, array_column($fieldMap, 'employeeFieldName'));
            $mapObject = $fieldMap[$mapObjectIndex];

            if (empty($mapObject) || empty($mapObject['azureKey']) || empty($azureUser[$mapObject['azureKey']]))
                continue;

            $user[$userField] = $azureUser[$mapObject['azureKey']];
        }

        $user['email'] = $user['workEmail'];
        unset($user['workEmail']);
        $user['employeeRoleId'] = $extraValues['employeeRoleId'];
        // $user['inactive'] = !($azureUser['accountEnabled'] ?? false);

        // user creation related validation
        $doesEmailAlreadyExists = !DB::table('user')->where('email', $user['email'])->get()->isEmpty();
        if ($doesEmailAlreadyExists)
            return [
                'error' => [...$errors, 'The email already exists'],
                'info' => $info,
                'data' => $user
            ];

        return [
            'error' => $errors,
            'info' => $info,
            'data' => $user
        ];
    }

    private function createSystemEmployeeJob($azureUser, $fieldMap)
    {
        $errors = [];
        $info = [];

        if (empty($azureUser['officeLocation'])) {
            $errors = ['Office Location is required'];
            return [
                'error' => $errors,
                'info' => $info,
                'data' => null
            ];
        }

        $job = [
            'effectiveDate' => $azureUser['employeeHireDate'],
            'locationId' => $this->getLocation($azureUser['officeLocation'])
        ];

        if (!empty($azureUser['jobTitle']))
            $job['jobTitleId'] = $this->getJobTitle($azureUser['jobTitle']);

        if (!empty($azureUser['companyName']) && !empty($azureUser['department'])) {
            $companyId = $this->getCompany($azureUser['companyName']);

            $job['departmentId'] = $this->getDepartment($companyId, $azureUser['department']);
        }

        if (!empty($azureUser['manager']))
            $job['reportsToEmployeeId'] = $this->getManager($azureUser['manager']);

        return [
            'error' => $errors,
            'info' => $info,
            'data' => $job
        ];
    }

    private function getJobTitle($jobTitle)
    {
        if (empty($jobTitle))
            return null;

        $existingRecordIndex = array_search($jobTitle, array_column($this->jobTitles, 'name'));

        if ($existingRecordIndex > 0)
            return $this->jobTitles[$existingRecordIndex]->id;

        $jobTitleId = DB::table('jobTitle')->insertGetId([
            'name' => $jobTitle
        ]);

        return $jobTitleId;
    }

    private function getCompany($company)
    {
        if (empty($company))
            return null;

        $existingRecordIndex = array_search($company, array_column($this->departments, 'name'));

        if ($existingRecordIndex > 0)
            return $this->departments[$existingRecordIndex]->id;

        $departmentId = DB::table('department')->insertGetId([
            'name' => $company
        ]);

        return $departmentId;
    }

    private function getDepartment($companyId, $department)
    {
        if (empty($companyId) || empty($department))
            return null;

        $existingRecordIndex = array_search($department, array_column($this->departments, 'name'));

        if ($existingRecordIndex > 0) {
            $departmentRecord = $this->departments[$existingRecordIndex];

            if (empty($departmentRecord->parentDepartmentId) || $departmentRecord->parentDepartmentId != $companyId) {
                $departmentId = DB::table('department')->insertGetId([
                    'name' => $department,
                    'parentDepartmentId' => $companyId
                ]);

                return $departmentId;
            }

            return $departmentRecord->id;
        }

        $departmentId = DB::table('department')->insertGetId([
            'name' => $department,
            'parentDepartmentId' => $companyId
        ]);

        return $departmentId;
    }

    private function getLocation($location)
    {
        if (empty($location))
            return null;

        $existingRecordIndex = array_search($location, array_column($this->locations, 'name'));

        if ($existingRecordIndex > 0)
            return $this->locations[$existingRecordIndex]->id;

        error_log('company > ' . json_encode($this->company));
        if (empty($this->company))
            return null;

        $locationId = DB::table('location')->insertGetId([
            'name' => $location,
            'timeZone' => $this->company->timeZone
        ]);

        return $locationId;
    }

    private function getManager($manager)
    {
        if (empty($manager))
            return null;

        $managerAzureId = $manager['id'];
        $managerAzureRecord = DB::table('azureUser')->where('azureObjectId', $managerAzureId)->first();

        return !empty($managerAzureRecord) && !empty($managerAzureRecord->employeeId) ? $managerAzureRecord->employeeId : null;
    }

    private function buildUserInsertingOrder($users, $managerUsers = [])
    {
        $orderedUser = [];

        if (empty($users)) {
            return $managerUsers;
        } else if (empty($managerUsers)) {
            $managers = array_column(array_filter($users, function ($user) {
                return $user->managerId == null;
            }), 'userId');

            $others = array_filter($users, function ($user) {
                return $user->managerId != null;
            });

            $nextLevelManagers = $this->buildUserInsertingOrder($others, $managers);
            $orderedUser = [...$managers, ...$nextLevelManagers];
        } else {
            $managers = array_column(array_filter($users, function ($user) use ($managerUsers) {
                return in_array($user->managerId, $managerUsers);
            }), 'userId');

            if (empty($managers)) {
                return [...$managerUsers, ...array_column($users, 'userId')];
            } else {
                $others = array_filter($users, function ($user) use ($managerUsers) {
                    return !in_array($user->managerId, $managerUsers);
                });

                $nextLevelManagers = $this->buildUserInsertingOrder($others, $managers);
                $orderedUser = [...$managers, ...$nextLevelManagers];
            }
        }

        return array_unique($orderedUser);
    }
}
