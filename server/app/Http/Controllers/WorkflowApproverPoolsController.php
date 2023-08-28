<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowApproverPoolsService;

/*
    Name: WorkflowApproverPoolsController
    Purpose: Performs request handling tasks related to the workflowApproverPool model.
    Description: API requests related to the workflowApproverPool model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class WorkflowApproverPoolsController extends Controller
{
    protected $workflowApproverPoolsService;

    /**
     * WorkflowApproverPoolsController constructor.
     *
     * @param WorkflowApproverPoolsService $workflowApproverPoolsService
     */
    public function __construct(WorkflowApproverPoolsService $workflowApproverPoolsService)
    {
        $this->workflowApproverPoolsService  = $workflowApproverPoolsService;
    }

   
    /**
     * Retrives all workflow approver pools
     */
    public function getAllWorkflowApproverPools(Request $request)
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
            "searchFields" => $request->query('search_fields', ['poolName']),
        ];
        $result = $this->workflowApproverPoolsService->getAllWorkflowApproverPools($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new workflow approver pool
    */
    public function createWorkflowApproverPool(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->workflowApproverPoolsService->createWorkflowApproverPool($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update Workflow approver pool
    */
    public function updateWorkflowApproverPool($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->workflowApproverPoolsService->updateWorkflowApproverPool($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Workflow approver pool
    */
    public function deleteWorkflowApproverPool($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->workflowApproverPoolsService->deleteWorkflowApproverPool($id);
        return $this->jsonResponse($result);
    }
    
    
}