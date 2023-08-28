<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkShiftService;

/*
    Name: WorkShiftController
    Purpose: Performs request handling tasks related to the Work Shift model.
    Description: API requests related to the Work Shift model are directed to this controller.
    Module Creator: Shobana
*/

class WorkShiftController extends Controller
{   

    protected $workShiftService;

    /**
     * WorkShiftController constructor.
     *
     * @param WorkShiftService $workShiftService
     */
    public function __construct(WorkShiftService $workShiftService)
    {
       
        $this->workShiftService  = $workShiftService;
    }

    /**
     * Create a new Work Shift .
     */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result =  $this->workShiftService->createWorkShift($data);
        return $this->jsonResponse($result);
    }
    
    
    
     /**
     * Get all Work Shifts
     */
    public function listAllWorkShifts(Request $request)
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sort', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('searchText', null),
            "searchFields" => $request->query('search_fields', ['name']),
        ];

        $result =  $this->workShiftService->listWorkShifts($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Get single Work Shift  by id
     */
    public function getById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result =  $this->workShiftService->getWorkShift($id);
        return $this->jsonResponse($result);
    }

   



    /**
     * UpdateWork Shift by id
     */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result =  $this->workShiftService->updateWorkShift($id, $request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Delete Work Shift  by id
     */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result =  $this->workShiftService->deleteWorkShift($id);
        return $this->jsonResponse($result);
    }

    /**
     * to get the workshift related info for the given dayType
     */
   
    public function getWorkShiftDayType(Request $request) {

        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result =  $this->workShiftService->getWorkShiftDayType($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * get workshift list with shiftName and Id
    */

    public function getWorkShiftsList() {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result =  $this->workShiftService->getWorkShiftsList();
        return $this->jsonResponse($result);
     }

    /**
     * following function create adhocworkshift 
    */
    public function createAdhocWorkShift(Request $request) {
        $permission = $this->grantPermission('shift-change');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result =  $this->workShiftService->createAdhocWorkShift($request->all());
        return $this->jsonResponse($result);
    }
}
