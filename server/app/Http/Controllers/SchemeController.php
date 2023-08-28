<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SchemeService;

/*
    Name: SchemeController
    Purpose: Performs request handling tasks related to the Scheme model.
    Description: API requests related to the Scheme model are directed to this controller.
    Module Creator: Shobana
*/

class SchemeController extends Controller
{
    protected $schemeService;

    /**
     * SchemeController constructor.
     *
     * @param SchemeService $schemeService
     */
    public function __construct(SchemeService $schemeService)
    {
        $this->schemeService  = $schemeService;
    }


    /*
        Creates a new Scheme.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->schemeService->createScheme($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Schemes
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
        $result = $this->schemeService->getAllSchemes($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Scheme based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->schemeService->getScheme($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Scheme based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->schemeService->getSchemeByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Scheme is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->schemeService->updateScheme($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Scheme
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->schemeService->softDeleteScheme($id);
        return $this->jsonResponse($result);
    }
}
