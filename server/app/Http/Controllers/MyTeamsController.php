<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyTeamsService;
use App\Services\EmployeeService;
use App\Library\Session;

/*
    Name: DepartmentController
    Purpose: Performs request handling tasks related to the Department model.
    Description: API requests related to the department model are directed to this controller.
    Module Creator: Hashan
*/

class MyTeamsController extends Controller
{
    protected $myTeamsService;
    protected $employeeService;
    protected $session;

    /**
     * DepartmentController constructor.
     *
     * @param MyTeamsService $departmentService
     */
    public function __construct(MyTeamsService $myTeamsService, EmployeeService $employeeService,  Session $session)
    {
        $this->myTeamsService  = $myTeamsService;
        $this->employeeService = $employeeService;
        $this->session = $session;
    }

    /**
     * Retrives all employees for a given employee id
     */

    public function getMyTeams(Request $request)
    {

        $permission = $this->grantPermission('my-teams-read', null, true);

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
            "searchFields" => $request->query('search_fields', ['employeeNumber', 'employeeName'])
        ];

        $result = $this->myTeamsService->getMyTeams($options, $permittedFields);
        // $result = $this->employeeService->getAllEmployees($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all employees
     */
    public function getAllEmployees(Request $request)
    {
        $permission = $this->grantPermission('my-teams-read', null, true);

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

        $result = $this->myTeamsService->getMyTeams($options, $permittedFields);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single employee based on employee_id.
    */
    public function getEmployee($id)
    {
        $permission = $this->grantPermission('my-teams-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->getEmployee($id);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new employee.
    */
    public function createEmployee(Request $request)
    {
        $permission = $this->grantPermission('my-teams-write');

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
        $permission = $this->grantPermission('my-teams-write');

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
        $permission = $this->grantPermission('my-teams-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeService->deleteEmployee($id);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new employee multi record.
    */
    public function createEmployeeMultiRecord($id, $multirecordAttribute, Request $request)
    {
        // $permission = $this->grantPermission('employee' . ucfirst($multirecordAttribute) . '-create');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->employeeService->createEmployeeMultiRecord($id, $multirecordAttribute, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee multi record is updated.
    */
    public function updateEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId, Request $request)
    {
        // $permission = $this->grantPermission('employee' . ucfirst($multirecordAttribute) . '-write');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->employeeService->updateEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee multi record is deleted.
    */
    public function deleteEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId)
    {
        // $permission = $this->grantPermission('employee' . ucfirst($multirecordAttribute) . '-write');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->employeeService->deleteEmployeeMultiRecord($id, $multirecordAttribute, $multirecordId);
        return $this->jsonResponse($result);
    }
}
