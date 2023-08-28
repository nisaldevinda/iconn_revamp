<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeaveEntitlementService;

/*
    Name: LeaveEntitlementController
    Purpose: Performs request handling tasks related to the LeaveEntitlement model.
    Description: API requests related to the LeaveEntitlement model are directed to this controller.
    Module Creator: Chalaka
*/

class LeaveEntitlementController extends Controller
{
    protected $leaveEntitlementService;
    

    /**
     * LeaveEntitlementController constructor.
     *
     * @param LeaveEntitlementService $leaveEntitlementService
     */
    public function __construct(LeaveEntitlementService $leaveEntitlementService)
    {
        $this->leaveEntitlementService  = $leaveEntitlementService;
    }


    /*
        Creates a new LeaveEntitlement.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('leave-entitlement-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->leaveEntitlementService->createLeaveEntitlement($data);
        return $this->jsonResponse($result);
    }

    /*
        Creates  new LeaveEntitlements.
    */
    public function storeMultiple(Request $request)
    {
        $permission = $this->grantPermission('leave-entitlement-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->leaveEntitlementService->createLeaveEntitlementMultiple($data);
        return $this->jsonResponse($result);
    }
    

    /**
     * Retrives all LeaveEntitlements
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', null);

        $filterData = (is_null($filter)) ? [] : json_decode($filter);

        $employeeId = property_exists($filterData, "employeeId") ? $filterData->employeeId : null;

        $permission = $this->grantPermission( 'leave-entitlement-read', "ADMIN", true, $employeeId);

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
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->leaveEntitlementService->getAllLeaveEntitlements($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single LeaveEntitlement based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission( 'leave-entitlement-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveEntitlementService->getLeaveEntitlement($id);
        return $this->jsonResponse($result);
    }


    

    /*
        A single LeaveEntitlement is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission( 'leave-entitlement-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveEntitlementService->updateLeaveEntitlement($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a LeaveEntitlement
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission( 'leave-entitlement-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveEntitlementService->softDeleteLeaveEntitlement($id);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all existingEmployes
     */
    public function getExistingEmployees(Request $request)
    {
        $permission = $this->grantPermission( 'leave-entitlement-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveEntitlementService->getExistingEmployees();
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all existing leaveTypes
     */
    public function getExistingLeaveTypes(Request $request)
    {
        $permission = $this->grantPermission( 'leave-entitlement-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveEntitlementService->getExistingLeaveTypes();
        return $this->jsonResponse($result);
    }
    
    /**
     * Retrives all existing leaveTypes
     */
    public function getExistingLeavePeriods(Request $request)
    {
        $permission = $this->grantPermission('leave-entitlement-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $options = [
            "leaveTypeId" => $request->query('selectedLeaveType', null),
            "employeeId" => $request->query('selectedEmployee', null),

        ];
        $result = $this->leaveEntitlementService->getExistingLeavePeriods($options);
        return $this->jsonResponse($result);
    }

    /**
     * Check Whether employee have entitilement for given leave period
     */
    public function checkEntitlementAvailability(Request $request)
    {
        $permission = $this->grantPermission('leave-entitlement-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = [
            "leaveTypeId" => $request->query('leaveTypeId', null),
            "employeeId" => $request->query('employeeId', null),
            "from" => $request->query('leavePeriodFrom', null),
            "to" => $request->query('leavePeriodTo', null),
        ];
        
        $result = $this->leaveEntitlementService->checkEntitlementAvailability($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives my LeaveEntitlements
     */

    public function myEntitlements(Request $request)
    {
        $permission = $this->grantPermission( 'leave-entitlement-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveEntitlementService->getMyLeaveEntitlements();
        return $this->jsonResponse($result);
    }


    
}
