<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ManualProcessService;

class ManualProcessController extends Controller
{
    protected $manualProcessService;

    public function __construct(ManualProcessService $manualProcessService)
    {
        $this->manualProcessService = $manualProcessService;
    }

    public function run(Request $request)
    {
        $permission = $this->grantPermission('manual-process');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->manualProcessService->run($request->all());
        return $this->jsonResponse($result);
    }

    public function history(Request $request)
    {
        $permission = $this->grantPermission('manual-process');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $type = $request->query('type', null);

        $result = $this->manualProcessService->history($type);
        return $this->jsonResponse($result);
    }
    /*
      following function retrieves the list of employees entitled to leave accrual process 
     */
    public function getLeaveAccrualProcessEmployeeList(Request $request)
    {
        $permission = $this->grantPermission('manual-process');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $manualProcessId = $request->query('manualProcessId', null);

        $result = $this->manualProcessService->getLeaveAccrualProcessEmployeeList($manualProcessId);
        return $this->jsonResponse($result);
    }
    
}
