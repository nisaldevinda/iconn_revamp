<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShiftAssignService;

/*
    Name: ShiftAssignController
    Purpose: Performs request handling tasks related to the ShiftAssign model.
    Description: API requests related to the ShiftAssign model are directed to this controller.
    Module Creator: Shobana
*/

class ShiftAssignController extends Controller
{   
    
    protected $shiftAssignService;

    /**
     * ShiftAssignController constructor.
     *
     * @param ShiftAssignService $shiftAssignService
     */
    public function __construct(ShiftAssignService $shiftAssignService)
    {
        $this->shiftAssignService  = $shiftAssignService;
    }

    /**
     * Create a new ShiftAssign .
     */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('shifts-assign-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result =  $this->shiftAssignService->createShiftAssign($request->all());
        return $this->jsonResponse($result);
    }
    
    /**
     * Get single ShiftAssign  by id
     */
    public function getById($id)
    {
        $permission = $this->grantPermission('shifts-assign-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
      
        $result =  $this->shiftAssignService->getShiftAssign($id);
        return $this->jsonResponse($result);
    }

    /**
     * get employees who is not assigned to any shifts
     */
   public function getShiftUnassignedEmployees(Request $request) {
       $permission = $this->grantPermission('shifts-assign-read-write');

        if (!$permission->check()) {
          return $this->forbiddenJsonResponse();
        }

        $params = [
            "locationId" => $request->query('locationId', null),
            "orgEntityId" => $request->query('orgEntityId', null),
            "targetKeys" => $request->query('targetKeys', []),
        ];

        $result =  $this->shiftAssignService->getShiftUnassignedEmployees($params);
        return $this->jsonResponse($result);
   }
}
