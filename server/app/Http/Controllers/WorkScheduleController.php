<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkScheduleService;
use App\Library\Session;

/*
    Name: WorkScheduleController
    Purpose: Performs request handling tasks related to the WorkSchedule model.
    Description: API requests related to the WorkSchedule model are directed to this controller.

*/

class WorkScheduleController extends Controller
{
    protected $workSchedule;
    protected $session;

    /**
     * WorkScheduleController constructor.
     *
     * @param WorkScheduleService $workSchedule
     */
    public function __construct(WorkScheduleService $workSchedule, Session $session)
    {
        $this->workSchedule  = $workSchedule;
        $this->session = $session;

    }


    /*
        Creates a new WorkSchedule.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('work-schedule-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result = $this->workSchedule->createAdhocWorkShift($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkSchedules
     */
    public function index(Request $request)
    {
        $permission = $this->grantPermission('work-schedule-read' , 'ADMIN' , true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getAllWorkSchedules($request->all());
        return $this->jsonResponse($result);
    }

    public function getEmployeeWorkPattern(Request $request) {

        $permission = $this->grantPermission('work-schedule-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $employeeId = [
            "empId" => $request->query('selectedEmp', null),
        ];
        $result = $this->workSchedule->getEmployeeWorkPattern($employeeId);
        return $this->jsonResponse($result);
    }

    /**
     * Create a new work pattern for employee
     */
    public function createEmployeeWorkPattern(Request $request)
    {
        $permission = $this->grantPermission('work-schedule-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result = $this->workSchedule->createEmployeeWorkPattern($data);
        return $this->jsonResponse($result);
    }
    /**
     * get the list of  workshifts
     */
    public function getWorkShifts() {
        $permission = $this->grantPermission('work-schedule-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getWorkShifts();
        return $this->jsonResponse($result);
    }

     /**
     * get workShift By id
     */
    public function getWorkShiftById($id) {
        $permission = $this->grantPermission('work-schedule-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getWorkShiftById($id);
        return $this->jsonResponse($result);
    }

         /**
     * get my workschedule by month
     */
    public function getMyWorkSchedule(Request $request) {
        $permission = $this->grantPermission('my-work-schedule');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $employeeId = $this->session->user->employeeId;

        $result = $this->workSchedule->getMyWorkSchedule($request,$employeeId);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives employee WorkSchedules
     */
    public function getEmployeeWorkSchedule(Request $request)
    {
        $permission = $this->grantPermission('work-schedule-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getEmployeeWorkSchedule($request->all());
        return $this->jsonResponse($result);
    }


    /**
     * Retrives employee WorkSchedules
     */
    public function getEmployeeWorkShiftByDate(Request $request)
    {
        $permission = $this->grantPermission('my-work-schedule');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getEmployeeWorkShiftByDate($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * get the list of  workshifts
     */
    public function getWorkShiftsForShiftChange() {
        $permission = $this->grantPermission('my-work-schedule');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getWorkShiftsForShiftChange();
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkSchedules
     */
    public function getWorkScheduleManagerView(Request $request)
    {
        $permission = $this->grantPermission('shift-change','MANAGER',true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workSchedule->getAllWorkSchedules($request->all());
        return $this->jsonResponse($result);
    }
}
