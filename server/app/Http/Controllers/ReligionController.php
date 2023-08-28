<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReligionService;

/*
    Name: ReligionController
    Purpose: Performs request handling tasks related to the Religion model.
    Description: API requests related to the Religion model are directed to this controller.
    Module Creator: Chalaka
*/

class ReligionController extends Controller
{
    protected $religionService;

    /**
     * ReligionController constructor.
     *
     * @param ReligionService $religionService
     */
    public function __construct(ReligionService $religionService)
    {
        $this->religionService  = $religionService;
    }


    /*
        Creates a new Religion.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->religionService->createReligion($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Religions
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
        $result = $this->religionService->getAllReligions($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Religion based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->religionService->getReligion($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Religion based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->religionService->getReligionByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Religion is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->religionService->updateReligion($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Religion
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->religionService->softDeleteReligion($id);
        return $this->jsonResponse($result);
    }
}
