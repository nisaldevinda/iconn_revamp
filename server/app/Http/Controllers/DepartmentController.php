<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DepartmentService;

/*
    Name: DepartmentController
    Purpose: Performs request handling tasks related to the Department model.
    Description: API requests related to the department model are directed to this controller.
    Module Creator: Hashan
*/

class DepartmentController extends Controller
{
    protected $departmentService;

    /**
     * DepartmentController constructor.
     *
     * @param DepartmentService $departmentService
     */
    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService  = $departmentService;
    }

    /**
     * Retrives all departments
     */
    public function getAllDepartments(Request $request)
    {   
        $permission = $this->grantPermission('master-data-read');

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
        $result = $this->departmentService->getAllDepartments($permittedFields, $options);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all departments without pagination and sorting.m
     */
    public function getAllRawDepartments()
    {
        $result = $this->departmentService->getAllRawDepartments();
        return $this->jsonResponse($result);
    }

    public function getEmployeesByDepartmentID($departmentID)
    {
        $result = $this->departmentService->getEmployeesByDepartmentID($departmentID);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single department based on department_id.
    */
    public function getDepartment($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->departmentService->getDepartment($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new department.
    */
    public function createDepartment(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->createDepartment($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single department is updated.
    */
    public function updateDepartment($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->updateDepartment($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single department is deleted.
    */
    public function deleteDepartment($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->deleteDepartment($id);
        return $this->jsonResponse($result);
    }

    public function generateDepartmentTree()
    {
        $permission = $this->grantPermission('org-chart-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->generateOrgTree();
        return $this->jsonResponse($result);
    }

    public function getManagerOrgChartData()
    {
        $permission = $this->grantPermission('manager-org-chart-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->getManagerOrgChartData();
        return $this->jsonResponse($result);
    }

    public function getManagerIsolatedOrgChartData($id)
    {
        $permission = $this->grantPermission('manager-org-chart-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->getManagerIsolatedOrgChartData($id);
        return $this->jsonResponse($result);
    }

    public function addEntity(Request $request)
    {
        $permission = $this->grantPermission('org-chart-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->addEntity($request->all());
        return $this->jsonResponse($result);
    }

    public function editEntity($id, Request $request)
    {
        $permission = $this->grantPermission('org-chart-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->editEntity($id, $request->all());
        return $this->jsonResponse($result);
    }

    public function deleteEntity($id)
    {
        $permission = $this->grantPermission('org-chart-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->departmentService->deleteEntity($id);
        return $this->jsonResponse($result);
    }

    public function getAllEntities()
    {
        $result = $this->departmentService->getAllEntities();
        return $this->jsonResponse($result);
    }

    public function getEntity($id)
    {
        $result = $this->departmentService->getEntity($id);
        return $this->jsonResponse($result);
    }

    public function canDelete($entityLevel)
    {
        $result = $this->departmentService->canDelete($entityLevel);
        return $this->jsonResponse($result);
    }
}
