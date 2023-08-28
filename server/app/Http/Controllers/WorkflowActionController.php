<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowActionService;
use Log;

/*
    Name: WorkflowActionController
    Purpose: Performs request handling tasks related to the WorkflowAction model.
    Description: API requests related to the WorkflowAction model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowActionController extends Controller
{
    protected $workflowActionService;

    /**
     * WorkflowActionController constructor.
     *
     * @param WorkflowActionService $workflowActionService
     */
    public function __construct(WorkflowActionService $workflowActionService)
    {
        $this->workflowActionService  = $workflowActionService;
    }


    /*
        Creates a new WorkflowAction.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowActionService->createWorkflowAction($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkflowAction
     */
    public function index(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

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
            "searchFields" => $request->query('search_fields', ['actionName']),
        ];
        $result = $this->workflowActionService->getAllWorkflowAction($permittedFields, $options);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all WorkflowAction
     */
    public function getWorkflowContextBaseAction(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $contextId =  $request->query('contextId', null);
        $result = $this->workflowActionService->getWorkflowContextBaseAction($contextId);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowAction based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowActionService->getWorkflowAction($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowAction based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->workflowActionService->getWorkflowActionByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single WorkflowAction is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowActionService->updateWorkflowAction($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a WorkflowAction
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowActionService->softDeleteWorkflowAction($id);
        return $this->jsonResponse($result);
    }

    /*
        Get accessible workflow actions by scope
    */
    public function accessibleWorkflowActions($workflowId, $employeeId, $instanceId, Request $request)
    {
        $permission = $this->grantPermission('workflow-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $scope = $request->query('scope', null);

        $result = $this->workflowActionService->accessibleWorkflowActions($workflowId, $employeeId, $scope, $instanceId);
        return $this->jsonResponse($result);
    }
}
