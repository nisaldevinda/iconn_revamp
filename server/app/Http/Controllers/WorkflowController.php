<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Log;
/*
    Name: WorkflowController
    Purpose: Performs request handling tasks related to the Workflow model.
    Description: API requests related to the Workflow model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowController extends Controller
{
    protected $workflowService;

    /**
     * WorkflowController constructor.
     *
     * @param WorkflowService $workflowService
     */
    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService  = $workflowService;
    }

    /*
        Creates a new Workflow.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
      
        $data = json_decode($request->getContent(), true);
        $result = $this->workflowService->createWorkflow($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Workflow
     */
    public function index($id,Request $request)
    {
        $permission = $this->grantPermission('employee-request', null, true);

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $queryParams = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filters" => $request->query('priorStateName', null),
            "contextType" => $request->query('contextType', null)
        ];
        $result = $this->workflowService->getAllWorkflow($id, $permittedFields, $queryParams);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Workflow based on id.
    */
    public function showById(Request $request)
    {
        $permission = $this->grantPermission('workflow-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $queryParams = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filters" => $request->query('priorStateName', null),
            "contextType" => $request->query('contextType', null)
        ];
        $result = $this->workflowService->getWorkflow($queryParams);
        return $this->jsonResponse($result);
    }

        /*
        Retrives a filter options for instances.
    */
    public function showByfilter()
    {
        $permission = $this->grantPermission('workflow-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowService->getWorkflowFilterOptions();
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Workflow based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowService->getWorkflowByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Workflow is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowService->updateWorkflow($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Workflow
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowService->softDeleteWorkflow($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives leave request related workflow states.
    */
    public function getLeaveRequestRelateWorkflowStates(Request $request)
    {
        $adminPermission = $this->grantPermission('admin-leave-request-access');
        $empPermission = $this->grantPermission('employee-leave-request-access');

        if (!$adminPermission->check() && !$empPermission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $sorter = $request->query('sorter', null);
    
        $result = $this->workflowService->getLeaveRequestRelatedStates($sorter);
        return $this->jsonResponse($result);
    }

    /*
        Retrives short leave request related workflow states.
    */
    public function getShortLeaveRequestRelateWorkflowStates(Request $request)
    {
        $adminPermission = $this->grantPermission('admin-leave-request-access');
        $empPermission = $this->grantPermission('employee-leave-request-access');

        if (!$adminPermission->check() && !$empPermission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $sorter = $request->query('sorter', null);
    
        $result = $this->workflowService->getShortLeaveRequestRelateWorkflowStates($sorter);
        return $this->jsonResponse($result);
    }

     /* Get pending count of Leave,attendance and Profile Request */ 
     public function getPendingRequestCount() {
        $permission = $this->grantPermission('todo-request-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowService->getPendingRequestCount();
        return $this->jsonResponse($result);

    }

    /*
        get approval level wise state of leave request
    */
    public function getApprovalLevelWiseStates($id)
    {
        $result = $this->workflowService->getApprovalLevelWiseStates($id);
        return $this->jsonResponse($result);
    }

    /*
        get approval level wise state of leave request
    */
    public function getWorkflowConfigTree($id)
    {
        $result = $this->workflowService->getWorkflowConfigTree($id);
        return $this->jsonResponse($result);
    }
    
}
