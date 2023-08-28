<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkCalendarService;

/*
    Name: WorkCalendarController
    Purpose: Performs request handling tasks related to the Work Calendar model.
    Description: API requests related to the Work Calendar Controller model are directed to this controller.
    Module Creator: Yohan
*/

class WorkCalendarController extends Controller
{
    protected $workCalendar;

    /**
     * WorkCalendarController constructor.
     *
     * @param WorkCalendarService $workCalendar
     */
    public function __construct(WorkCalendarService $workCalendar)
    {
        $this->workCalendar  = $workCalendar;
    }


    /*
        Creates a new WorkCalendar.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->workCalendar->createWorkCalendar($data);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new Special Date.
    */
    public function creatSpecialDate(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->workCalendar->creatSpecialDate($data);
        return $this->jsonResponse($result);
    }

    /*
        Sends a list of created WorkCalendars
    */
    public function getCalendarList()
    {
        // $permission = $this->grantPermission('work-calendar-read-write');
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $timeObject = $this->workCalendar->getCalendarList();
        return $this->jsonResponse($timeObject);
    }

    /*
        Sends a new Meta Date List of a given month and year.
    */
    public function getCalendarMetaData(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarParams = [
            "calendarId" => $request->query('calendarId', null),
            "month" => $request->query('month', null),
            "year" => $request->query('year', null)
        ];

        $calendarMetaData = $this->workCalendar->getCalendarMetaData($calendarParams);
        return $this->jsonResponse($calendarMetaData);
    }

    /*
        Sends a new Summery of a given calendar.
    */
    public function getCalendarSummery(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarSummeryParams = [
            "calendarId" => $request->query('calendarId', null),
            "year" => $request->query('year', null)
        ];
        $calendarMetaData = $this->workCalendar->getCalendarSummmery($calendarSummeryParams);
        return $this->jsonResponse($calendarMetaData);
    }

    /*
        Sends a List of date types.
    */
    public function getCalendarDateTypes()
    {
        $permission = $this->grantPermission('work-calendar-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workCalendar->getCalendarDateTypes();
        return $calendarDateTypes;
    }

    /*
        Edit calendar date name.
    */
    public function editCalendarName($id, Request $request)
    {
        $permission = $this->grantPermission('work-calendar-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workCalendar->editCalendarName($id, $request->all());
        return $calendarDateTypes;
    }
}
