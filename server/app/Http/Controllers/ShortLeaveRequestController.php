<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShortLeaveRequestService;

/*
    Name: ShortLeaveRequestController
    Purpose: Performs request handling tasks related to the shortLeaveRequest model.
    Description: API requests related to the short leave model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ShortLeaveRequestController extends Controller
{
    protected $shortLeaveRequestService;

    /**
     * ShortLeaveRequestController constructor.
     *
     * @param ShortLeaveRequestService $leaveRequestService
     */
    public function __construct(ShortLeaveRequestService $shortLeaveRequestService)
    {
        $this->shortLeaveRequestService  = $shortLeaveRequestService;
    }

    
    /*
        Creates a new short leave request.
    */
    public function createShortLeave(Request $request)
    {
        $permission = $this->grantPermission('ess');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->shortLeaveRequestService->createShortLeave($request->all(), 'shortLeaveRequest');
        return $this->jsonResponse($result);
    }
    

    /*
        Assign a new short leave request to particular employee.
    */
    public function assignShortLeave(Request $request)
    {

        $permission = $this->grantPermission('assign-leave');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->shortLeaveRequestService->assignShortLeave($request->all());
        return $this->jsonResponse($result);
    }

    /*
        get short leave request related attachments.
    */
    public function getShortLeaveAttachments(Request $request)
    {

        $permission = $this->grantPermission('access-leave-attachments');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $id = $request->query('id', null);
        
        $result = $this->shortLeaveRequestService->getShortLeaveAttachments($id);
        return $this->jsonResponse($result);
    }

    /*
        get employee short leave history data according to page count, page size, search, and sort.
    */
    public function getEmployeeShortLeavesHistoryData(Request $request)
    {
        $permission = $this->grantPermission('employee-leave-request-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->shortLeaveRequestService->getEmployeeShortLeavesHistoryData($request);
        return $this->jsonResponse($result);
    }

    /*
        get admin short leave history data according to page count, page size, search, and sort.
    */
    public function getAdminShortLeavesHistoryData(Request $request)
    {
        $permission = $this->grantPermission('admin-leave-request-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->shortLeaveRequestService->getAdminShortLeavesHistoryData($request);
        return $this->jsonResponse($result);
    }
    
    /*
        get selected date is working day
    */
    public function getShortLeaveDateIsWorkingDay(Request $request)
    {
        $date = $request->query('date', null);
        $employeeId = $request->query('employeeId', null);

        $result = $this->shortLeaveRequestService->getShortLeaveDateIsWorkingDay($date, $employeeId);
        return $this->jsonResponse($result);
    }
    
    /*
        Apply a new short leave request on behalf of employee.
    */
    public function applyTeamShortLeave(Request $request)
    {
        $permission = $this->grantPermission('apply-team-leave');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->shortLeaveRequestService->createShortLeave($request->all(), 'adminShortLeaveRequest');
        return $this->jsonResponse($result);
    }

    /*
        cancel short leave covering person request.
    */
    public function cancelShortLeaveRequest($id, Request $request)
    {
        
        // $permission = $this->grantPermission('employee-leave-request-access');
        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }
            
        $result = $this->shortLeaveRequestService->cancelShortLeaveRequest($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        cancel short leave assign by admin
    */
    public function cancelAdminAssignShortLeaveRequest($id)
    {
        
        $permission = $this->grantPermission('assign-leave');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->shortLeaveRequestService->cancelAdminAssignShortLeaveRequest($id);
        return $this->jsonResponse($result);
    }
}
