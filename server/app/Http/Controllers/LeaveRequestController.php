<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeaveRequestService;

/*
    Name: LeaveRequestController
    Purpose: Performs request handling tasks related to the LeaveRequest model.
    Description: API requests related to the leave model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class LeaveRequestController extends Controller
{
    protected $leaveRequestService;

    /**
     * LeaveTypeController constructor.
     *
     * @param LeaveRequestService $leaveRequestService
     */
    public function __construct(LeaveRequestService $leaveRequestService)
    {
        $this->leaveRequestService  = $leaveRequestService;
    }

    /**
     * Retrives all leave requests
     */
    public function getAllLeaves(Request $request)
    {
        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', '{"fromDate":"descend"}'),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', []),
        ];
        $result = $this->leaveRequestService->getAllLeaves($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single leave request based on leave_type_id.
    */
    public function getLeave($id)
    {
        
        $result = $this->leaveRequestService->getLeave($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new leave request.
    */
    public function createLeave(Request $request)
    {
        $permission = $this->grantPermission('ess');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveRequestService->createLeave($request->all(), 'leaveRequest');
        return $this->jsonResponse($result);
    }
    
    /*
        A single leave request is updated.
    */
    public function updateLeave($id, Request $request)
    {
        $result = $this->leaveRequestService->updateLeave($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single leave request is deleted.
    */
    public function deleteLeave($id)
    {
        $result = $this->leaveRequestService->deleteLeave($id);
        return $this->jsonResponse($result);
    }

    /*
        Assign a new leave request to particular employee.
    */
    public function assignLeave(Request $request)
    {

        $permission = $this->grantPermission('assign-leave');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveRequestService->assignLeave($request->all());
        return $this->jsonResponse($result);
    }

    /*
        get leave request related attachments.
    */
    public function getLeaveAttachments(Request $request)
    {
        $permission = $this->grantPermission('access-leave-attachments');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $id = $request->query('id', null);
        
        $result = $this->leaveRequestService->getLeaveAttachments($id);
        return $this->jsonResponse($result);
    }


    /*
        get leave request related attachments.
    */
    public function getLeaveCoveringRequests(Request $request)
    {
        $permission = $this->grantPermission('ess');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $queryParams = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "stateName" => $request->query('stateName', null),
        ];

        $result = $this->leaveRequestService->getLeaveCoveringRequests($queryParams);
        return $this->jsonResponse($result);
    }
    
    /*
        get manager leave request  data according to page count, page size, search, and sort.
    */
    public function getManagerLeaveRequestData(Request $request)
    {
        $permission = $this->grantPermission('manager-leave-request-access', null, true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->leaveRequestService->getManagerLeaveRequestData($request);
        return $this->jsonResponse($result);
    }
    
    /*
        get employee view leave request data according to page count, page size, search, and sort.
    */
    public function getEmployeeLeaveRequestData(Request $request)
    {
        $permission = $this->grantPermission('employee-leave-request-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->leaveRequestService->getEmployeeLeaveRequestData($request);
        return $this->jsonResponse($result);
    }


    /*
        get employee view leave request data according to page count, page size, search, and sort.
    */
    public function getEmployeeLeavesHistory(Request $request)
    {
        $permission = $this->grantPermission('employee-leave-request-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->leaveRequestService->getEmployeeLeavesHistory($request);
        return $this->jsonResponse($result);
    }
    
    /*
        get Admin view of leave request data according to page count, page size, search, and sort.
    */
    public function getAdminLeavesHistory(Request $request)
    {
        $permission = $this->grantPermission('admin-leave-request-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->leaveRequestService->getAdminLeavesHistory($request);
        return $this->jsonResponse($result);
    }

    /*
        get Admin view of leave request data according to page count, page size, search, and sort.
    */
    public function getAdminLeaveRequestData(Request $request)
    {
        $permission = $this->grantPermission('admin-leave-request-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->leaveRequestService->getAdminLeaveRequestData($request);
        return $this->jsonResponse($result);
    }
        
    /*
        get leave request related comments.
    */
    public function getLeaveRequestRelatedComments($id)
    {
        
        $permission = $this->grantPermission('leave-request-comments-read-write');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->leaveRequestService->getLeaveRequestRelatedComments($id);
        return $this->jsonResponse($result);
    }

    /*
        get leave request related comments.
    */
    public function getLeaveDateWiseDetails($id)
    {
             
        $result = $this->leaveRequestService->getLeaveDateWiseDetails($id);
        return $this->jsonResponse($result);
    }


    /*
        cancel leave covering person request.
    */
    public function cancelCoveringPersonBasedLeaveRequest($id)
    {
        
        $permission = $this->grantPermission('employee-leave-request-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->leaveRequestService->cancelCoveringPersonBasedLeaveRequest($id);
        return $this->jsonResponse($result);
    }

    /*
        cancel leave request assign by admin
    */
    public function cancelAdminAssignLeaveRequest($id)
    {
        
        $permission = $this->grantPermission('assign-leave');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->leaveRequestService->cancelAdminAssignLeaveRequest($id);
        return $this->jsonResponse($result);
    }

    /*
        cancel leave covering person request.
    */
    public function cancelLeaveRequestDates($id, Request $request)
    {
        
        // $permission = $this->grantPermission('employee-leave-request-access');
        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }
            
        $result = $this->leaveRequestService->cancelLeaveRequestDates($id, $request->all());
        return $this->jsonResponse($result);
    }


    /*
        add leave request comment
    */
    public function addLeaveRequestComment($id, Request $request)
    {
        $permission = $this->grantPermission('leave-request-comments-read-write');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->leaveRequestService->addLeaveRequestComment($id, $request->all());
        return $this->jsonResponse($result);
    }


    /*
        update leave covering person request state
    */
    public function updateLeaveCoveringPersonRequest(Request $request)
    {
        $permission = $this->grantPermission('ess');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveRequestService->updateLeaveCoveringPersonRequest($request->all());
        return $this->jsonResponse($result);
    }
     
    /*
        get leave entitlement usage data or report according to page count, page size, search, and sort.
    */
    public function getLeaveEntitlementUsage(Request $request)
    {
        $permission = $this->grantPermission('leave-entitlement-report-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->leaveRequestService->getLeaveEntitlementUsage($request);
        return $this->jsonResponse($result);
    }

    public function calculateWorkingDaysCountForLeave(Request $request)
    {
        $leaveTypeId = $request->query('leaveTypeId', null);
        $fromDate = $request->query('fromDate', null);
        $toDate = $request->query('toDate', null);
        $employeeId = $request->query('employeeId', null);

        $result = $this->leaveRequestService->calculateWorkingDaysCountForLeave($leaveTypeId, $fromDate, $toDate, $employeeId);
        return $this->jsonResponse($result);
    }


    public function getShiftDataForLeaveDate(Request $request)
    {
        $fromDate = $request->query('fromDate', null);
        $toDate = $request->query('toDate', null);
        $employeeId = $request->query('employeeId', null);

        $result = $this->leaveRequestService->getShiftDataForLeaveDate($fromDate, $toDate, $employeeId);
        return $this->jsonResponse($result);
    }

    public function checkShortLeaveAccessabilityForCompany(Request $request)
    {
        $result = $this->leaveRequestService->checkShortLeaveAccessabilityForCompany();
        return $this->jsonResponse($result);
    }


    /*
        get manager leave request  data according to page count, page size, search, and sort.
    */
    public function exportManagerLeaves(Request $request)
    {
        $permission = $this->grantPermission('manager-leave-request-access', null, true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->leaveRequestService->exportManagerLeaves($request);
        return $this->jsonResponse($result);
    }

    /*
        get Admin view of leave request data according to page count, page size, search, and sort.
    */
    public function exportAdminLeaves(Request $request)
    {
        $permission = $this->grantPermission('admin-leave-request-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
            
        $result = $this->leaveRequestService->exportAdminLeaves($request);
        return $this->jsonResponse($result);
    }
   
    /*
        Apply a new leave request on behalf of employee.
    */
    public function applyTeamLeave(Request $request)
    {
        $permission = $this->grantPermission('apply-team-leave');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->leaveRequestService->createLeave($request->all(), 'adminLeaveRequest');
        return $this->jsonResponse($result);
    }
    
}
