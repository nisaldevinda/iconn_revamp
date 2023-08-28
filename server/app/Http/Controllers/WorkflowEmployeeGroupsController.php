<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowEmployeeGroupService;

/*
    Name: WorkflowEmployeeGroupsController
    Purpose: Performs request handling tasks related to the workflowEmployeeGroup model.
    Description: API requests related to the workflowEmployeeGroup model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class WorkflowEmployeeGroupsController extends Controller
{
    protected $workflowEmployeeGroupService;

    /**
     * WorkflowEmployeeGroupsController constructor.
     *
     * @param WorkflowEmployeeGroupService $workflowEmployeeGroupService
     */
    public function __construct(WorkflowEmployeeGroupService $workflowEmployeeGroupService)
    {
        $this->workflowEmployeeGroupService  = $workflowEmployeeGroupService;
    }

   
    /**
     * Retrives all workflow employee group
     */
    public function getAllWorkflowEmployeeGroups(Request $request)
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
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->workflowEmployeeGroupService->getAllWorkflowEmployeeGroups($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new workflow employee group
    */
    public function createWorkflowEmployeeGroup(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->workflowEmployeeGroupService->createWorkflowEmployeeGroup($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update Employee group
    */
    public function updateWorkflowEmployeeGroup($id, Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->workflowEmployeeGroupService->updateWorkflowEmployeeGroup($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Employee Group
    */
    public function deleteWorkflowEmployeeGroup($id)
    {
        $permission = $this->grantPermission('workflow-management-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->workflowEmployeeGroupService->deleteWorkflowEmployeeGroup($id);
        return $this->jsonResponse($result);
    }
    
    
}