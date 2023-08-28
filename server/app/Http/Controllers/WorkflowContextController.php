<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowContextService;
use Log;

/*
    Name: WorkflowContextController
    Purpose: Performs request handling tasks related to the WorkflowContext model.
    Description: API requests related to the WorkflowContext model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowContextController extends Controller
{
    protected $workflowContextService;

    /**
     * WorkflowContextController constructor.
     *
     * @param WorkflowContextService $workflowContextService
     */
    public function __construct(WorkflowContextService $workflowContextService)
    {
        $this->workflowContextService  = $workflowContextService;
    }


    /*
        Creates a new WorkflowContext.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowContextService->createWorkflowContext($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkflowContext
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
            "searchFields" => $request->query('search_fields', ['contextName']),
        ];
        $result = $this->workflowContextService->getAllWorkflowContext($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowContext based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowContextService->getWorkflowContext($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowContext based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->workflowContextService->getWorkflowContextByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single WorkflowContext is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowContextService->updateWorkflowContext($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a WorkflowContext
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowContextService->softDeleteWorkflowContext($id);
        return $this->jsonResponse($result);
    }
}
