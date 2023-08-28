<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowStateService;
use Log;

/*
    Name: WorkflowStateController
    Purpose: Performs request handling tasks related to the WorkflowState model.
    Description: API requests related to the WorkflowState model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowStateController extends Controller
{
    protected $workflowStateService;

    /**
     * WorkflowStateController constructor.
     *
     * @param WorkflowStateService $workflowStateService
     */
    public function __construct(WorkflowStateService $workflowStateService)
    {
        $this->workflowStateService  = $workflowStateService;
    }


    /*
        Creates a new WorkflowState.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateService->createWorkflowState($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkflowState
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
            "searchFields" => $request->query('search_fields', ['stateName']),
        ];
        $result = $this->workflowStateService->getAllWorkflowState($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowState based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateService->getWorkflowState($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowState based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->workflowStateService->getWorkflowStateByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single WorkflowState is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateService->updateWorkflowState($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a WorkflowState
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateService->softDeleteWorkflowState($id);
        return $this->jsonResponse($result);
    }
}
