<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceService;
use App\Library\Session;


class AttendanceController extends Controller
{
    protected $attendanceService;
    protected $session;
    /**
     * Attendance constructor.
     *
     * @param AttendanceService $attendanceService
     */

    public function __construct(AttendanceService $attendanceService, Session $session)
    {
        $this->attendanceService  = $attendanceService;
        $this->session = $session;
    }

    /*
        Manage Attendance.
    */
    public function manageAttendance(Request $request)
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $requestData = (object) $request->all();
        $currentStatus = $requestData->status;

        $result = $this->attendanceService->manageAttendance($currentStatus);
        return $this->jsonResponse($result);
    }

    /*
        get Attendance.
    */
    public function getAttendance(Request $request)
    {
        $isShiftSummary = filter_var($request->query('summary', false), FILTER_VALIDATE_BOOLEAN);

        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->attendanceService->getAttendanceRelatedData($isShiftSummary);
        return $this->jsonResponse($result);
    }

    /*
        Manage Break.
    */
    public function manageBreak(Request $request)
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $requestData = (object) $request->all();
        $currentStatus = $requestData->status;

        $result = $this->attendanceService->manageBreak($currentStatus);
        return $this->jsonResponse($result);
    }

    /*
        Manage last logged.
    */
    public function  getLastLoggedTime()
    {
        $permission = $this->grantPermission('attendance-employee-access');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $employeeId = $this->session->user->employeeId;
        $result = $this->attendanceService->getLastLoggedTime($employeeId);
        return $this->jsonResponse($result);
    }

    /*
      funtion is to save data in attendance backup log
    */
    public function createAttendanceLog(Request $request)
    { 
        $result = $this->attendanceService->createAttendanceLog($request->all());
        return $this->jsonResponse($result);
    }
}
