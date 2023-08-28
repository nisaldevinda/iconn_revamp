<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DivisionService;
use App\Services\EmployeeNumberConfigService;

class EmployeeNumberConfigController extends Controller
{
    protected $employeeNumberConfigService;

    /**
     * EmployeeNumberConfigController constructor.
     *
     * @param EmployeeNumberConfigService $employeeNumberConfigService
     */
    public function __construct(EmployeeNumberConfigService $employeeNumberConfigService)
    {
        $this->employeeNumberConfigService  = $employeeNumberConfigService;
    }

    /**
     * Retrives all employee number configs
     */
    public function getAllEmployeeNumberConfigs(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', '{"name":"descend"}'),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->employeeNumberConfigService->getAllEmployeeNumberConfigs($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single employee number config based on id.
    */
    public function getEmployeeNumberConfigs($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeNumberConfigService->getEmployeeNumberConfigs($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new employee number config.
    */
    public function addEmployeeNumberConfigs(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeNumberConfigService->addEmployeeNumberConfigs($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee number config is updated.
    */
    public function updateEmployeeNumberConfigs($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeNumberConfigService->updateEmployeeNumberConfigs($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single employee number config is deleted.
    */
    public function removeEmployeeNumberConfigs($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employeeNumberConfigService->removeEmployeeNumberConfigs($id);
        return $this->jsonResponse($result);
    }
}
