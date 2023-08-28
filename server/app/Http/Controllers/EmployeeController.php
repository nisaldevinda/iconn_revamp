<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Library\Session;

use App\Services\EmployeeService;
use App\Services\DivisionService;

use App\Services\AutoGenerateIdService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeeExport;
use DB;
/*
    Name: EmployeeController
    Purpose: Performs request handling tasks related to the Employee model.
    Description: API requests related to the employee model are directed to this controller.
    Module Creator: Hashan
*/

class EmployeeController extends Controller
{
    protected $employeeService;
    protected $autoGenerateIdService;
    protected $divisionService;

    /**
     * EmployeeController constructor.
     *
     * @param EmployeeService $employeeService
     */
    public function __construct(EmployeeService $employeeService, AutoGenerateIdService $autoGenerateIdService, DivisionService $divisionService)
    {
        $this->employeeService  = $employeeService;
        $this->autoGenerateIdService  = $autoGenerateIdService;
        $this->divisionService  = $divisionService;
    }

    /**
     * Retrives all employees
     */
    public function getAllEmployees(Request $request)
    {
        $permission = $this->grantPermission('employee-read', null, true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['employeeNumber', 'employeeName']),
        ];

        $result = $this->employeeService->getAllEmployees($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single employee based on employee_id.
    */
    public function getEmployee($id)
    {
        $permission = $this->grantPermission('employee-read', null, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployee($id);
        return $this->jsonResponse($result);
    }


    /*
        Retrives a single employee based on employee_id.
    */
    public function checkIsShiftAllocated($id)
    {
        $permission = $this->grantPermission('employee-read', null, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->checkIsShiftAllocated($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new employee.
    */
    public function createEmployee(Request $request)
    {
        $permission = $this->grantPermission('employee-create');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->createEmployee($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee is updated.
    */
    public function updateEmployee($id, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->updateEmployee($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee is deleted.
    */
    public function deleteEmployee($id)
    {
        $permission = $this->grantPermission('employee-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->deleteEmployee($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single employee most recent employement.
    */
    public function getEmployeeCurrent($id)
    {
        $result = $this->employeeService->getEmployeeCurrent($id);
        return $this->jsonResponse($result);
    }

    /*    get recent birthdays
    */
    public function getAdminUpcomingBirthDays(Request $request)
    {
        $permission = $this->grantPermission('admin-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUpcomingBirthDays();
        return $this->jsonResponse($result);
    }

    /*    get recent birthdays
    */
    public function getManagerUpcomingBirthDays(Request $request)
    {
        $permission = $this->grantPermission('manager-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUpcomingBirthDays();
        return $this->jsonResponse($result);
    }

    /*    get recent birthdays
    */
    public function getEmployeeUpcomingBirthDays(Request $request)
    {
        $permission = $this->grantPermission('employee-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUpcomingBirthDays();
        return $this->jsonResponse($result);
    }

    /*
        get recent Anniversaries
    */
    public function getAdminUpcomingAnniversaryDays(Request $request)
    {
        $permission = $this->grantPermission('admin-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUpcomingAnniversaryDays();
        return $this->jsonResponse($result);
    }

    /*
        get recent Anniversaries
    */
    public function getManagerUpcomingAnniversaryDays(Request $request)
    {
        $permission = $this->grantPermission('manager-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUpcomingAnniversaryDays();
        return $this->jsonResponse($result);
    }

    /*
        get recent Anniversaries
    */
    public function getEmployeeUpcomingAnniversaryDays(Request $request)
    {
        $permission = $this->grantPermission('employee-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUpcomingAnniversaryDays();
        return $this->jsonResponse($result);
    }

    /*
        A single employee is changed active/deactive status.
    */
    public function changeEmployeeActiveStatus($id, Request $request)
    {
        $permission = $this->grantPermission('employee-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->changeEmployeeActiveStatus($id, $request->all());
        return $this->jsonResponse($result);
    }


    /*
    get current employement details
    */
    public function getCurrentJob($id)
    {
        $result = $this->employeeService->getCurrentJob($id);
        return $this->jsonResponse($result);
    }

    /*
        get current employee data
    */
    public function getMyProfile()
    {
        $permission = $this->grantPermission('my-profile');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getMyProfile();
        return $this->jsonResponse($result);
    }

    /*
        get current employee data
    */
    public function getMyProfileView()
    {
        $permission = $this->grantPermission('my-profile');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getMyProfileView();
        return $this->jsonResponse($result);
    }

    /*
        The purpose of this function is to send a unique employee ID
    */
    public function getEmployeeId()
    {

        $autoGenerateIdData["prefix"] = "EMP";
        $autoGenerateIdData["modelType"] = "employee";

        $result = $this->autoGenerateIdService->getAutoGeneratedNumber($autoGenerateIdData);

        return $this->jsonResponse($result);
    }

    /*
        The purpose of this function is to get a unique employee ID for form building
    */
    public function getCurrentEmployeeId()
    {   
        $result = $this->autoGenerateIdService->getIncrementingNumber();

        return $this->jsonResponse($result);
    }

    /*
        get employee field access permissions.
    */
    public function getEmployeeFieldAccessPermission($id, Request $request)
    {
        $roleType = $request->query('type', 'EMPLOYEE');

        $permission = $this->grantPermission('master-data-read', $roleType, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeFieldAccessPermission();
        return $this->jsonResponse($result);
    }

    /*
        Creates a new employee multi record.
    */
    public function createEmployeeMultiRecord($id, $multirecordAttribute, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->createEmployeeMultiRecord($id, $multirecordAttribute, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee multi record is updated.
    */
    public function updateEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->updateEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee multi record is deleted.
    */
    public function deleteEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId)
    {
        $permission = $this->grantPermission('employee-write', null, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->deleteEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId);
        return $this->jsonResponse($result);
    }

    public function getEmployeeProfilePicture($id)
    {
        $result = $this->employeeService->getEmployeeProfilePicture($id);
        return $this->jsonResponse($result);
    }

    public function storeEmployeeProfilePicture($id, Request $request)
    {
        $scope = $request->query('scope', null);
        $permission = $this->grantPermission('upload-profile-picture', $scope, true, $id);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->storeEmployeeProfilePicture($id, $request->all(), false);
        return $this->jsonResponse($result);
    }

    public function removeEmployeeProfilePicture($id)
    {
        $result = $this->employeeService->removeEmployeeProfilePicture($id);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all managers
     */
    public function getAllManagers(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['employeeNumber', 'employeeName']),
        ];

        $result = $this->employeeService->getAllManagers($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all workflow permitted managers 
     */
    public function getAllWorkflowPermittedManagers(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $workflowId =  $request->query('workflowId', null);

        if (!empty($workflowId)) {
            $result = $this->employeeService->getAllWorkflowPermittedManagers($workflowId);
        } else {
            $result = $this->employeeService->getAllWorkflowPermittedManagersAndAdmins($workflowId);

        }

        return $this->jsonResponse($result);
    }

    public function getEmployeeOrgChart()
    {
        $permission = $this->grantPermission('org-chart-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeOrgChart();
        return $this->jsonResponse($result);
    }

    public function getUserWiseEmployees(Request $request)
    {
        $permission = $this->grantPermission('assign-leave', null, true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $sortBy = $request->query('sortByAsc', null);

        $result = $this->employeeService->getPermitedEmployeesForUser($sortBy);
        return $this->jsonResponse($result);
    }

    public function updateMyProfile(Request $request, Session $session)
    {
        $permission = $this->grantPermission('my-profile');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $employeeId = $session->employee->id;
        $result = $this->employeeService->updateEmployee($employeeId, $request->all(), true);
        return $this->jsonResponse($result);
    }

    public function createMyProfileMultiRecord($multirecordAttribute, Request $request, Session $session)
    {
        $permission = $this->grantPermission('my-profile');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $session->employee->id;
        $result = $this->employeeService->createEmployeeMultiRecord($employeeId, $multirecordAttribute, $request->all(), true);
        return $this->jsonResponse($result);
    }

    public function updateMyProfileMultiRecord($multirecordAttribute, $multirecordId, Request $request, Session $session)
    {
        $permission = $this->grantPermission('my-profile');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $session->employee->id;
        $result = $this->employeeService->updateEmployeeMultiRecord($employeeId, $multirecordAttribute, $multirecordId, $request->all(), true);
        return $this->jsonResponse($result);
    }

    public function deleteMyProfileMultiRecord($multirecordAttribute, $multirecordId, Session $session)
    {
        $permission = $this->grantPermission('my-profile');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $session->employee->id;
        $result = $this->employeeService->deleteEmployeeMultiRecord($employeeId, $multirecordAttribute, $multirecordId, true);
        return $this->jsonResponse($result);
    }

    public function getMyProfilePicture($id)
    {
        $permission = $this->grantPermission('my-profile');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeProfilePicture($id);
        return $this->jsonResponse($result);
    }

    public function storeMyProfilePicture($id, Request $request)
    {
        $permission = $this->grantPermission('my-profile');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->storeEmployeeProfilePicture($id, $request->all(), true);
        return $this->jsonResponse($result);
    }

    public function removeMyProfilePicture($id)
    {
        $permission = $this->grantPermission('my-profile');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->removeEmployeeProfilePicture($id);
        return $this->jsonResponse($result);
    }

    /*
       Get all the employees with name and id
    */
    public function getEmployeeList(Request $request)
    {
        $scope = $request->query("scope", null);

        if (is_null($scope)) {
            $permission = $this->grantPermission('master-data-read');
        } else {
            $permission = $this->grantPermission('master-data-read', $scope, true);
        }

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeList($scope);
        return $this->jsonResponse($result);
    }

    /*
       Get all the employees with name and id
    */
    public function getAllEmployeeList(Request $request)
    {
        $scope = $request->query("scope", null);

        if (is_null($scope)) {
            $permission = $this->grantPermission('master-data-read');
        } else {
            $permission = $this->grantPermission('master-data-read', $scope, true);
        }

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getAllEmployeeList($scope);
        return $this->jsonResponse($result);
    }

    /*
       Get all the employees with name and id
    */
    public function getEmployeeListByEntityId(Request $request)
    {
        $scope = $request->query("scope", null);
        $entityId = $request->query("entityId", null);

        $permission = $this->grantPermission('master-data-read', $scope, true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeListByEntityId($scope, $entityId);
        return $this->jsonResponse($result);
    }

    /*
        Retrives  employees based on keyword.
    */
    public function getEmployeesByKeyword(Request $request)
    {
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $options = [
            "departmentId" => $request->query('departmentId', null),
            "locationId" => $request->query('locationId', null),
        ];
        $result = $this->employeeService->getEmployeesByKeyword($options);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all employees by filter
     */
    public function getAllEmployeesFiltered(Request $request)
    {
        $permission = $this->grantPermission('employee-read', null, true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['employeeNumber', 'employeeName']),
        ];

        $result = $this->employeeService->getAllEmployeesFiltered($permittedFields, $options);
        return $this->jsonResponse($result);
    }


    public function getMyProfileMultiRecord($multirecordAttribute, $multirecordId, Session $session)
    {
        $permissionMyRequest = $this->grantPermission('my-request');
        $permissionEmployeeRequest = $this->grantPermission('employee-request');
        if (!$permissionMyRequest->check() && !$permissionEmployeeRequest->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeMultiRecord($multirecordAttribute, $multirecordId);
        return $this->jsonResponse($result);
    }

    public function getMyProfileRelationalData(Request $request)
    {
        $permissionMyRequest = $this->grantPermission('my-request');
        $permissionEmployeeRequest = $this->grantPermission('employee-request');
        if (!$permissionMyRequest->check() && !$permissionEmployeeRequest->check()) {
            return $this->forbiddenJsonResponse();
        }

        $relationModels = $request->query('relationModels', null);
        $relationModels = json_decode($relationModels);

        $result = $this->employeeService->getMyProfileRelationalData($relationModels);
        return $this->jsonResponse($result);
    }

    public function getProfileUpdateDataDiff(Request $request)
    {
        $scope = $request->query('scope', null);
        error_log($scope);
        $permissionMyRequest = $this->grantPermission('employee-profile-diff', $scope);
        if (!$permissionMyRequest->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getProfileUpdateDataDiff($request);
        return $this->jsonResponse($result);
    }

    public function getEmployeeSideCardDetails($id)
    {
        $result = $this->employeeService->getEmployeeSideCardDetails($id);
        return $this->jsonResponse($result);
    }

    /* 
       get the list of Unassigned employees to the User 
    */
    public function getUnassignedEmployees(Request $request)
    {
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->employeeService->getUnassignedEmployees($request->all());
        return $this->jsonResponse($result);
    }

    /* get the list of subordinates for the selected manager*/
    public function getSubordinatesForSelectedManager($id) {
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getSubordinatesForSelectedManager($id);
        return $this->jsonResponse($result);
    }

    /* get employees for selected Department and location*/
    public function getEmployeesForDepartmentAndLocation(Request $request) {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeesForDepartmentAndLocation($request->all());
        return $this->jsonResponse($result);  
    }

    /* get employees for selected Department*/
    public function getEmployeesForDepartment($id) {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeesForDepartment($id);
        return $this->jsonResponse($result);
    }
    /* get employees for selected location*/
    public function getEmployeesForLocation($id) {
        
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }


        $result = $this->employeeService->getEmployeesForLocation($id);
        return $this->jsonResponse($result);    
    }


    /* get employees by employee number*/
    public function getEmployeeByEmployeeNumber(Request $request) {
        
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $employeeNumber = $request->query('employeeNumber', null);


        $result = $this->employeeService->getEmployeeByEmployeeNumber($employeeNumber);
        return $this->jsonResponse($result);    
    }

    /* 
       get the list of root employees  
    */
    public function getRootEmployees()
    {
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getRootEmployees();
        return $this->jsonResponse($result);
    }

    /*
       get Employee Number format to show validation in the client side 
    */
    public function getEmployeeNumberFormat()
    {
        $result = $this->autoGenerateIdService->getEmployeeNumberFormat();

        return $this->jsonResponse($result);
    }

    /*Api end point to get the profile picture image */
    public function getEmpProfilePicture($id) {
        
        $result = $this->employeeService->getEmpProfilePicture($id);
        return $result;
    }

    /**
     * get next employee number
    */
    public function getNextEmployeeNumber(Request $request){
        $permission = $this->grantPermission('employee-create');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
 
        $result = $this->autoGenerateIdService->getNextEmployeeNumber($request->all());

        return $this->jsonResponse($result);
    }

    /*
    *function is to save employee number configuration 
    */
    public function addEmployeeNumberConfig(Request $request){
        
        $permission = $this->grantPermission('employee-create');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->autoGenerateIdService->addEmployeeNumberConfig($request->all());

        return $this->jsonResponse($result);
    }

    /*
       Get all the employees for covering person dropdown
    */
    public function getEmployeeListForCoveringPerson(Request $request)
    {
        $permission = $this->grantPermission('covering-person-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployeeListForCoveringPerson();
        return $this->jsonResponse($result);
    }

    /*
       Get all the employees for claim allocation
    */
    public function getEmployeeListForClaimAllocation(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $selectedClaimType = $request->query('selectedClaimType', null);
        $selectedFinacialYear = $request->query('selectedFinacialYear', null);

        $result = $this->employeeService->getEmployeeListForClaimAllocation($selectedClaimType,$selectedFinacialYear);
        return $this->jsonResponse($result);
    }
}
