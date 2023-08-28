<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceService;
use App\Services\EmployeeService;

class AttendanceSheetController extends Controller
{
    protected $attendanceService;
    protected $employeeService;
    /**
     * Attendance Sheet constructor.
     *
     * @param AttendanceService $attendanceService
     * @param EmployeeService $employeeService
     */

    public function __construct(AttendanceService $attendanceService, EmployeeService $employeeService)
    {
        $this->attendanceService  = $attendanceService;
        $this->employeeService  = $employeeService;
    }

    /*
        get Attendance Sheet Employees per manager.
    */
    public function getEmployeesPerManager(Request $request)
    {
        $result = $this->employeeService->getEmployeesPerManager($request);
        return $this->jsonResponse($result);
    }

    /*
        get Attendance data according to page count, page size, search, and sort.
    */
    public function getManagerAttendanceSheetData(Request $request)
    {
        $permission = $this->grantPermission('attendance-manager-access', 'MANAGER', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->getManagerAttendanceSheetData($request);
        return $this->jsonResponse($result);
    }


    /*
        get Attendance data according to page count, page size, search, and sort.
    */
    public function getAttendanceReportsData(Request $request)
    {
        $permission = $this->grantPermission('attendance-admin-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->getAttendanceReportsData($request);
        return $this->jsonResponse($result);
    }

    /*
        get Attendance data according to page count, page size, search, and sort.
    */
    public function getEmployeeAttendanceSheetData(Request $request)
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->getEmployeeAttendanceSheetData($request);
        return $this->jsonResponse($result);
    }

    /*
        get Attendance data for post ot request.
    */
    public function getPostOtRequestAttendanceSheetData(Request $request)
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->getPostOtRequestAttendanceSheetData($request);
        return $this->jsonResponse($result);
    }

    /*
        get post ot request details by post ot request id.
    */
    public function getAttendanceDetailsByPostOtRequestId($id)
    {
        $result = $this->attendanceService->getAttendanceDetailsByPostOtRequestId($id);
        return $this->jsonResponse($result);
    }

    /*
        get Attendance related breake details
    */
    public function getAttendanceRelatedBreaks(Request $request)
    {

        $result = $this->attendanceService->getAttendanceRelatedBreaks($request);
        return $this->jsonResponse($result);
    }

    /*
        check ot calculation access for employee
    */
    public function checkOtAccessability(Request $request)
    {
        $result = $this->attendanceService->checkOtAccessability($request);
        return $this->jsonResponse($result);
    }

    /*
        check ot calculation access for employee
    */
    public function checkOtAccessabilityForCompany(Request $request)
    {
        $result = $this->attendanceService->checkOtAccessabilityForCompany($request);
        return $this->jsonResponse($result);
    }

    /*
        get user Attendance summary data according to date.
    */
    public function getDailySummeryData(Request $request)
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $date = $request->query('date', null);

        $result = $this->attendanceService->getDailySummeryData(null, $date);
        return $this->jsonResponse($result);
    }

    /*
        get Attendance summary data according to date and employee.
    */
    public function getOthersDailySummeryData(Request $request)
    {
        $scope = $request->query('scope', null);

        $permission = $this->grantPermission('attendance-employee-summery', $scope, true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $request->query('employeeId', null);
        $date = $request->query('date', null);

        $result = $this->attendanceService->getDailySummeryData($employeeId, $date);
        return $this->jsonResponse($result);
    }

    /*
        requesting to update Attendance punch in and out for an employee.
    */
    public function updateAttendanceTime(Request $request)
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->updateAttendanceTime($request->all());
        return $this->jsonResponse($result);
    }

    /*
        updating break records for perticular attendance summery record
    */
    public function updateBreakRecordsAdmin(Request $request)
    {
        $permission = $this->grantPermission('attendance-admin-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->updateBreakRecordsAdmin($request->all());
        return $this->jsonResponse($result);
    }

    /*
        request Attendance time change date for an employee.
    */
    public function requestAttendanceTime(Request $request)
    {
        $permission = $this->grantPermission('attendance-manager-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->requestAttendanceTime($request);
        return $this->jsonResponse($result);
    }

    /*
        approve Attendance time change request for an employee.
    */
    public function approveAttendanceTime(Request $request)
    {
        $permission = $this->grantPermission('attendance-manager-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->approveAttendanceTime($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Change Attendance time for an employee.
    */
    public function updateAttendanceTimeAdmin(Request $request)
    {
        $permission = $this->grantPermission('attendance-admin-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->updateAttendanceTimeAdmin($request->all());
        return $this->jsonResponse($result);
    }


    /*
        Update invalid attendance records
    */
    public function updateInvalidAttendances(Request $request)
    {
        $scope = (!empty($request->all()['scope'])) ? $request->all()['scope'] : null;
        if ($scope == 'ADMIN') {
            $permission = $this->grantPermission('invalid-attendance-update-admin-access');
        } elseif ($scope == 'MANAGER') {
            $permission = $this->grantPermission('invalid-attendance-update-manager-access');
        }
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->updateInvalidAttendances($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update invalid attendance records
    */
    public function createPostOtRequest(Request $request)
    {
        $permission = $this->grantPermission('ess');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->attendanceService->createPostOtRequest($request->all());
        return $this->jsonResponse($result);
    }

    /*
        get Admin view of Attendance data according to page count, page size, search, and sort.
    */
    public function getAdminAttendanceSheetData(Request $request)
    {
        $permission = $this->grantPermission('attendance-admin-access', 'ADMIN', true);
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->getAdminAttendanceSheetData($request);
        return $this->jsonResponse($result);
    }

    /*
        get invalid attendance records
    */
    public function getInvalidAttendanceSheetData(Request $request)
    {
        $scope = $request->query('scope', null);
        if ($scope == 'ADMIN') {
            $permission = $this->grantPermission('invalid-attendance-update-admin-access', $scope, true);
        } elseif ($scope == 'MANAGER') {
            $permission = $this->grantPermission('invalid-attendance-update-manager-access', $scope, true);
        }

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->getInvalidAttendanceData($request);
        return $this->jsonResponse($result);
    }

    /*
         download excel sheet of a manager related employees attendance.
    */
    public function downloadManagerAttendanceView(Request $request)
    {
        $permission = $this->grantPermission('attendance-manager-access', 'MANAGER', true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->downloadTeamAttendance($request);
        return $this->jsonResponse($result);
    }
    /*
        download excel sheet of a admin related employees attendance.
    */
    public function downloadAdminAttendanceView(Request $request)
    {
        $permission = $this->grantPermission('attendance-admin-access', 'ADMIN', true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->attendanceService->downloadTeamAttendance($request);
        return $this->jsonResponse($result);
    }
}
