<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkCalendarDayTypeService;

/*
    Name: WorkCalendarDayTypeController
    Purpose: Performs request handling tasks related to the Work Calendar Day Type model.
    Description: API requests related to the Work Calendar Day Type Controller model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class WorkCalendarDayTypeController extends Controller
{
    protected $workCalendarDayTypeService;

    /**
     * WorkCalendarDayTypeController constructor.
     *
     * @param WorkCalendarDayTypeService $workCalendarDayTypeService
     */
    public function __construct(WorkCalendarDayTypeService $workCalendarDayTypeService)
    {
        $this->workCalendarDayTypeService  = $workCalendarDayTypeService;
    }


    /*
        Creates a new WorkCalendarDayType.
    */
    public function createDayType(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-day-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->workCalendarDayTypeService->createWorkCalendarDayType($data);
        return $this->jsonResponse($result);
    }

  

    /*
        Sends a List of date types.
    */
    public function getDayTypeList(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-day-type-read-write');

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
            "searchFields" => $request->query('search_fields', null),
        ];
        $result = $this->workCalendarDayTypeService->getDayTypeList($permittedFields, $options);

        return $result;
    }
    /*
        Sends a List of base date types.
    */
    public function getAllBaseDayTypeList(Request $request)
    {
        $permission = $this->grantPermission('work-calendar-day-type-read-write');

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
            "searchFields" => $request->query('search_fields', null),
        ];
        $result = $this->workCalendarDayTypeService->getAllBaseDayTypeList($permittedFields, $options);

        return $result;
    }

    /*
        Edit calendar day type.
    */
    public function updateDayType($id, Request $request)
    {
        $permission = $this->grantPermission('work-calendar-day-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workCalendarDayTypeService->updateCaledarDayTypeData($id, $request->all());
        return $calendarDateTypes;
    }


    /*
        Delete Calendar day type
    */
    public function deleteDayType($id, Request $request)
    {

        $permission = $this->grantPermission('work-calendar-day-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workCalendarDayTypeService->deleteWorkCalendarDayType($id);
        return $calendarDateTypes;
    }
}
