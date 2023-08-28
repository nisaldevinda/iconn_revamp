<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DivisionService;

/*
    Name: DivisionController
    Purpose: Performs request handling tasks related to the Division model.
    Description: API requests related to the division model are directed to this controller.
    Module Creator: Hashan
*/

class DivisionController extends Controller
{
    protected $divisionService;

    /**
     * DivisionController constructor.
     *
     * @param DivisionService $divisionService
     */
    public function __construct(DivisionService $divisionService)
    {
        $this->divisionService  = $divisionService;
    }

    /**
     * Retrives all divisions
     */
    public function getAllDivisions(Request $request)
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
        $result = $this->divisionService->getAllDivisions($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single division based on division_id.
    */
    public function getDivision($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->divisionService->getDivision($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new division.
    */
    public function createDivision(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->divisionService->createDivision($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single division is updated.
    */
    public function updateDivision($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->divisionService->updateDivision($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single division is deleted.
    */
    public function deleteDivision($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->divisionService->deleteDivision($id);
        return $this->jsonResponse($result);
    }
}
