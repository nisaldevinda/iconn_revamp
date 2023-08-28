<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SalaryComponentsService;
use Log;

/*
    Name: SalaryComponentsController
    Purpose: Performs request handling tasks related to the SalaryComponents model.
    Description: API requests related to the SalaryComponents model are directed to this controller.
    Module Creator: Chalaka
*/

class SalaryComponentsController extends Controller
{
    protected $salaryComponentsService;

    /**
     * SalaryComponentsController constructor.
     *
     * @param SalaryComponentsService $salaryComponentsService
     */
    public function __construct(SalaryComponentsService $salaryComponentsService)
    {
        $this->salaryComponentsService  = $salaryComponentsService;
    }


    /*
        Creates a new SalaryComponents.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->salaryComponentsService->createSalaryComponents($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all SalaryComponents
     */
    public function index(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $permittedFields = ["*"];
        $queryParams = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->salaryComponentsService->getAllSalaryComponents($permittedFields, $queryParams);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single SalaryComponents based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->salaryComponentsService->getSalaryComponents($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single SalaryComponents based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->salaryComponentsService->getSalaryComponentsByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single SalaryComponents is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->salaryComponentsService->updateSalaryComponents($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a SalaryComponents
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->salaryComponentsService->softDeleteSalaryComponents($id);
        return $this->jsonResponse($result);
    }
}
