<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MaritalStatusService;

/*
    Name: MaritalStatusController
    Purpose: Performs request handling tasks related to the MaritalStatus model.
    Description: API requests related to the MaritalStatus model are directed to this controller.
    Module Creator: Chalaka
*/

class MaritalStatusController extends Controller
{
    protected $maritalStatusService;

    /**
     * MaritalStatusController constructor.
     *
     * @param MaritalStatusService $maritalStatusService
     */
    public function __construct(MaritalStatusService $maritalStatusService)
    {
        $this->maritalStatusService  = $maritalStatusService;
    }


    /*
        Creates a new MaritalStatus.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->maritalStatusService->createMaritalStatus($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all MaritalStatus
     */
    public function index(Request $request)
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
        $result = $this->maritalStatusService->getAllMaritalStatus($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single MaritalStatus based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->maritalStatusService->getMaritalStatus($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single MaritalStatus based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->maritalStatusService->getMaritalStatusByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single MaritalStatus is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->maritalStatusService->updateMaritalStatus($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a MaritalStatus
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->maritalStatusService->softDeleteMaritalStatus($id);
        return $this->jsonResponse($result);
    }
}
