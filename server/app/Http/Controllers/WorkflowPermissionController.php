<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowPermissionService;
use Log;

/*
    Name: WorkflowPermissionController
    Purpose: Performs request handling tasks related to the WorkflowPermission model.
    Description: API requests related to the WorkflowPermission model are directed to this controller.
    Module Creator: Tharindu
*/

class WorkflowPermissionController extends Controller
{
    protected $workflowPermissionService;

    /**
     * WorkflowPermissionController constructor.
     *
     * @param WorkflowPermissionService $workflowPermissionService
     */
    public function __construct(WorkflowPermissionService $workflowPermissionService)
    {
        $this->workflowPermissionService  = $workflowPermissionService;
    }


    /*
        Creates a new WorkflowPermission.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowPermissionService->createWorkflowPermission($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all WorkflowPermission
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
            "searchFields" => $request->query('search_fields', ['roleId']),
        ];
        $result = $this->workflowPermissionService->getAllWorkflowPermission($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowPermission based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->workflowPermissionService->getWorkflowPermission($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single WorkflowPermission based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowPermissionService->getWorkflowPermissionByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single WorkflowPermission is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowPermissionService->updateWorkflowPermission($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a WorkflowPermission
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->workflowPermissionService->softDeleteWorkflowPermission($id);
        return $this->jsonResponse($result);
    }
}
