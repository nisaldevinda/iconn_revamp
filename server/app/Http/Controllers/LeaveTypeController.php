<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeaveTypeService;

/*
    Name: LeaveTypeController
    Purpose: Performs request handling tasks related to the LeaveType  model.
    Description: API requests related to the leave type model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class LeaveTypeController extends Controller
{
    protected $leaveTypeService;

    /**
     * LeaveTypeController constructor.
     *
     * @param LeaveTypeService $leaveTypeService
     */
    public function __construct(LeaveTypeService $leaveTypeService)
    {
        $this->leaveTypeService  = $leaveTypeService;
    }

    /**
     * Retrives all leave types
     */
    public function getAllLeaveTypes(Request $request)
    {
        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', '{"name":"descend"}'),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->leaveTypeService->getAllLeaveTypes($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single leave type based on leave_type_id.
    */
    public function getLeaveType($id)
    {
        
        $result = $this->leaveTypeService->getLeaveType($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new Leave Type.
    */
    public function createLeaveType(Request $request)
    {
        $result = $this->leaveTypeService->createLeaveType($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single leave type is updated.
    */
    public function updateLeaveType($id, Request $request)
    {
        $result = $this->leaveTypeService->updateLeaveType($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single Leave type is deleted.
    */
    public function deleteLeaveType($id)
    {
        $result = $this->leaveTypeService->deleteLeaveType($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives leave types which can apply by current employee.
    */
    public function getLeaveTypesForApplyLeave()
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveTypeService->getLeaveTypesForApplyLeave();
        return $this->jsonResponse($result);
    }

    /*
        Retrives leave types which can assign by current user.
    */
    public function getLeaveTypesForAssignLeave(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $request->query('employeeId', null);

        $result = $this->leaveTypeService->getLeaveTypesForAssignLeave($employeeId);
        return $this->jsonResponse($result);
    }

    /*
        Retrives leave types which can assign by current user.
    */
    public function getLeaveTypesForAdminApplyLeaveForEmployee(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $request->query('employeeId', null);

        $result = $this->leaveTypeService->getLeaveTypesForAdminApplyLeaveForEmployee($employeeId);
        return $this->jsonResponse($result);
    }

    public function getEmployeeEntitlementCount(Request $request)
    {
        $date = $request->query('date', null);
        $employeeId = $request->query('employee', null);
        $accessLevel = $request->query('access-level', 'Apply-Leave');

        $result = $this->leaveTypeService->getEmployeeEntitlementCount($date, $employeeId, null, $accessLevel);
        return $this->jsonResponse($result);
    }

            /*
       Get all the leave types with name and id
    */
    public function getLeaveTypesList(Request $request)
    {
        $scope = $request->query("scope", null);

        if (is_null($scope)) {
            $permission = $this->grantPermission('leave-entitlement-read');
        } else {
            $permission = $this->grantPermission('leave-entitlement-read', $scope, true);
        }

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveTypeService->getLeaveTypesList();
        return $this->jsonResponse($result);
    }

                /*
       Get all the leave types with name and id
    */
    public function getLeaveTypesWorkingDays()
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeService->getLeaveTypesWorkingDays();
        return $this->jsonResponse($result);
    }
}


