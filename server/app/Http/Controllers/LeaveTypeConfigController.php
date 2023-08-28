<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeaveTypeConfigService;

/*
    Name: LeaveTypeConfigController
    Purpose: Performs request handling tasks related to the LeaveTypeConfig  model.
    Description: API requests related to the leave type model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class LeaveTypeConfigController extends Controller
{
    protected $leaveTypeConfigService;

    /**
     * LeaveTypeConfigController constructor.
     *
     * @param LeaveTypeConfigService $leaveTypeConfigService
     */
    public function __construct(LeaveTypeConfigService $leaveTypeConfigService)
    {
        $this->leaveTypeConfigService  = $leaveTypeConfigService;
    }

    /**
     * Retrives all leave types
     */
    public function getWhoCanApply($id)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->getWhoCanApply($id);
        return $this->jsonResponse($result);
    }
    /**
     * Retrives all leave types
     */
    public function getAllEmployeeGroups()
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->getAllEmployeeGroups();
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all leave types
     */
    public function getAllLeaveTypeWiseAccruals(Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $leaveTypeId = $request->query('leaveTypeId', null);
        $result = $this->leaveTypeConfigService->getAllLeaveTypeWiseAccruals($leaveTypeId);
        return $this->jsonResponse($result);
    }
    
    /**
     * Retrives all employee groups by leave type
     */
    public function getEmployeeGroupsByLeaveTypeId(Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');
        $leaveTypeId = $request->query('leaveTypeId', null);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->getEmployeeGroupsByLeaveTypeId($leaveTypeId);
        return $this->jsonResponse($result);
    }
    
    
    /*
        Creates a new Leave Type.
    */
    public function createWhoCanApply(Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->createWhoCanApply($request->all());
        return $this->jsonResponse($result);
    }


    /*
        Creates a new Employee group
    */
    public function updateEmployeeGroup($id, Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->updateEmployeeGroup($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Employee Group
    */
    public function deleteLeaveEmployeeGroup($id)
    {
        $permission = $this->grantPermission('leave-type-config');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->deleteLeaveEmployeeGroup($id);
        return $this->jsonResponse($result);
    }
    
    /*
        Set Leave Type Accrual Configs
    */
    public function setLeaveTypeAccrualConfigs($id, Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->setLeaveTypeAccrualConfigs($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Set Leave Type Accrual Configs
    */
    public function createLeaveAccrualConfig(Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->createLeaveAccrualConfig($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Set Leave Type Accrual Configs
    */
    public function updateLeaveAccrualConfig($id, Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->updateLeaveAccrualConfig($id, $request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives Leave Type Related Accrual Configs
     */
    public function getLeaveTypeAccrualConfigsByLeaveTypeId(Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');
        $leaveTypeId = $request->query('leaveTypeId', null);
        $accrualFrequency = $request->query('accrualFrequency', null);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveTypeConfigService->getLeaveTypeAccrualConfigsByLeaveTypeId($leaveTypeId, $accrualFrequency);
        return $this->jsonResponse($result);
    }
}