<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowStateTransitionService;
use Log;

/*
    Name: WorkflowStateTransitionController
    Purpose: Performs request handling tasks related to the WorkflowStateTransition model.
    Description: API requests related to the WorkflowStateTransition model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowStateTransitionController extends Controller
{
    protected $workflowStateTransitionService;

    /**
     * WorkflowStateTransitionController constructor.
     *
     * @param WorkflowStateTransitionService $workflowStateTransitionService
     */
    public function __construct(WorkflowStateTransitionService $workflowStateTransitionService)
    {
        $this->workflowStateTransitionService  = $workflowStateTransitionService;
    }


    /*
        Creates a new WorkflowStateTransition.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateTransitionService->createWorkflowStateTransition($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkflowStateTransition
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
            "filterBy"=>$request->query('filterBy',null),
        ];
        $result = $this->workflowStateTransitionService->getAllWorkflowStateTransition($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowStateTransition based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateTransitionService->getWorkflowStateTransition($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowStateTransition based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateTransitionService->getWorkflowStateTransitionByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single WorkflowStateTransition is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateTransitionService->updateWorkflowStateTransition($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a WorkflowStateTransition
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowStateTransitionService->softDeleteWorkflowStateTransition($id);
        return $this->jsonResponse($result);
    }
}
