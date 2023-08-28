<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowDefineService;
use Log;

/*
    Name: WorkflowDefineController
    Purpose: Performs request handling tasks related to the WorkflowDefine model.
    Description: API requests related to the WorkflowDefine model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowDefineController extends Controller
{
    protected $workflowDefineService;

    /**
     * WorkflowDefineController constructor.
     *
     * @param WorkflowDefineService $workflowDefineService
     */
    public function __construct(WorkflowDefineService $workflowDefineService)
    {
        $this->workflowDefineService  = $workflowDefineService;
    }


    /*
        Creates a new WorkflowDefine.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->createWorkflowDefine($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkflowDefine
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
            "searchFields" => $request->query('search_fields', ['workflowName']),
        ];
        $result = $this->workflowDefineService->getAllWorkflowDefine($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowDefine based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->getWorkflowDefine($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowDefine based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->workflowDefineService->getWorkflowDefineByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single WorkflowDefine is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->updateWorkflowDefine($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a WorkflowDefine
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->softDeleteWorkflowDefine($id);
        return $this->jsonResponse($result);
    }

    /*
        A single Workflow is updated.
    */
    public function updateWorkflowProcedureType($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->updateWorkflowProcedureType($id, $request->all());
        return $this->jsonResponse($result);
    }


    /*
        A single Workflow is updated.
    */
    public function updateWorkflowLevelConfigurations($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->updateWorkflowLevelConfigurations($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single Workflow is updated.
    */
    public function deleteWorkflowLevelConfigurations($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->deleteWorkflowLevelConfigurations($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new WorkflowDefine.
    */
    public function addWorkflowApproverLevel(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowDefineService->addWorkflowApproverLevel($request->all());
        return $this->jsonResponse($result);
    }
}
