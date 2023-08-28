<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShiftChangeRequestService;

/*
    Name: ShiftChangeRequestController
    Purpose: Performs request handling tasks related to the shortLeaveRequest model.
    Description: API requests related to the short leave model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ShiftChangeRequestController extends Controller
{
    protected $shiftChangeRequestService;

    /**
     * ShiftChangeRequestController constructor.
     *
     * @param ShiftChangeRequestService $leaveRequestService
     */
    public function __construct(ShiftChangeRequestService $shiftChangeRequestService)
    {
        $this->shiftChangeRequestService  = $shiftChangeRequestService;
    }


    /*
        Creates a new short leave request.
    */
    public function createShiftChangeRequest(Request $request)
    {
        $permission = $this->grantPermission('ess');
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->shiftChangeRequestService->createShiftChangeRequest($request->all());
        return $this->jsonResponse($result);
    }
}
