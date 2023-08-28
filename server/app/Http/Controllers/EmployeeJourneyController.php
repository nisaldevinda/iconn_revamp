<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmployeeJourneyService;

/*
    Name: EmployeeJourneyController
    Purpose: Performs request handling tasks related to the Division model.
    Description: API requests related to the division model are directed to this controller.
    Module Creator: Hashan
*/

class EmployeeJourneyController extends Controller
{
    protected $employeeJourneyService;

    /**
     * EmployeeJourneyController constructor.
     *
     * @param EmployeeJourneyService $employeeJourneyService
     */
    public function __construct(EmployeeJourneyService $employeeJourneyService)
    {
        $this->employeeJourneyService  = $employeeJourneyService;
    }

    /*
        store employee journey promotion.
    */
    public function createNewPromotion($employeeId, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $employeeId);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->createEmployeeJourneyEvent($employeeId, 'PROMOTIONS', $request->all());
        return $this->jsonResponse($result);
    }

    /*
        store employee journey promotion.
    */
    public function contractRenewal($employeeId, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $employeeId);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->createEmployeeJourneyEvent($employeeId, 'CONFIRMATION_CONTRACTS', $request->all());
        return $this->jsonResponse($result);
    }

    /*
        store employee journey promotion.
    */
    public function createNewTransfer($employeeId, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $employeeId);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->createEmployeeJourneyEvent($employeeId, 'TRANSFERS', $request->all());
        return $this->jsonResponse($result);
    }

    /*
        store employee journey promotion.
    */
    public function createResignation($employeeId, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $employeeId);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->createEmployeeJourneyEvent($employeeId, 'RESIGNATIONS', $request->all());
        return $this->jsonResponse($result);
    }

    /*
        send resignation request.
    */
    public function sendResignationRequest(Request $request)
    {
        $permission = $this->grantPermission('ess');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->createEmployeeResignationRequest($request->all());
        return $this->jsonResponse($result);
    }

    /*
        update current employee job.
    */
    public function updateCurrentJob($employeeId, Request $request)
    {
        $scope = $request->query('scope');

        if ($scope == 'EMPLOYEE') {
            $permission = $this->grantPermission('my-profile', null, true, $employeeId);
        } else if ($scope == 'MANAGER') {
            $permission = $this->grantPermission('my-teams-write', null, true, $employeeId);
        } else {
            $permission = $this->grantPermission('employee-write', null, true, $employeeId);
        }

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->updateCurrentJob($employeeId, $request->all(), $scope);
        return $this->jsonResponse($result);
    }

    /*
        fetch attachment.
    */
    public function getAttachment($employeeId, $fileId)
    {
        // $permission = $this->grantPermission('employee-read', null, true, $employeeId);

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->employeeJourneyService->getAttachment($fileId);
        return $this->jsonResponse($result);
    }

    /*
        fetch resignation attachment.
    */
    public function getResignationAttachment($fileId)
    {
        // $permission = $this->grantPermission('ess');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->employeeJourneyService->getResignationAttachment($fileId);
        return $this->jsonResponse($result);
    }

    /*
        Update upcoming employee journey milestone.
    */
    public function reupdateUpcomingEmployeeJourneyMilestone($employeeId, $id, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $employeeId);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->reupdateUpcomingEmployeeJourneyMilestone($employeeId, $id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Rollback upcoming employee journey milestone.
    */
    public function rollbackUpcomingEmployeeJourneyMilestone($employeeId, $id, Request $request)
    {
        $permission = $this->grantPermission('employee-write', null, true, $employeeId);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->rollbackUpcomingEmployeeJourneyMilestone($employeeId, $id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        get rejoin eligible list.
    */
    public function getRejoinEligibleList()
    {
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->getRejoinEligibleList();
        return $this->jsonResponse($result);
    }

    /*
        get reactive eligible list.
    */
    public function getReactiveEligibleList()
    {
        $permission = $this->grantPermission('employee-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->getReactiveEligibleList();
        return $this->jsonResponse($result);
    }

    /*
        rejoin employee.
    */
    public function rejoinEmployee($employeeId, Request $request)
    {
        $permission = $this->grantPermission('employee-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->rejoinEmployee($employeeId, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        reactive employee.
    */
    public function reactiveEmployee($employeeId, Request $request)
    {
        $permission = $this->grantPermission('employee-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeJourneyService->reactiveEmployee($employeeId, $request->all());
        return $this->jsonResponse($result);
    }
}
