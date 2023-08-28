<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NationalityService;

/*
    Name: NationalityController
    Purpose: Performs request handling tasks related to the Nationality model.
    Description: API requests related to the Nationality model are directed to this controller.
    Module Creator: Chalaka
*/

class NationalityController extends Controller
{
    protected $nationalityService;

    /**
     * NationalityController constructor.
     *
     * @param NationalityService $nationalityService
     */
    public function __construct(NationalityService $nationalityService)
    {
        $this->nationalityService  = $nationalityService;
    }


    /*
        Creates a new Nationality.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->nationalityService->createNationality($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Nationalities
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
        $result = $this->nationalityService->getAllNationalities($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Nationality based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->nationalityService->getNationality($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Nationality based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->nationalityService->getNationalityByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Nationality is updated.
    */
    public function update($id, Request $request)
    {
         $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->nationalityService->updateNationality($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Nationality
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->nationalityService->softDeleteNationality($id);
        return $this->jsonResponse($result);
    }
}
