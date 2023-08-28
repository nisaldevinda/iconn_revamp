<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamicModelDataService;

/*
    Name: DynamicModelDataController
    Purpose: Performs request handling tasks related to the DynamicModelData model.
    Description: API requests related to the user model are directed to this controller.
    Module Creator: Chalaka
*/

class DynamicModelDataController extends Controller
{
    protected $dynamicModelDataService;

    /**
     * DynamicModelDataController constructor.
     *
     * @param DynamicModelDataService $dynamicModelDataService
     */
    public function __construct(DynamicModelDataService $dynamicModelDataService)
    {
        $this->dynamicModelDataService  = $dynamicModelDataService;
    }

    /*
        Creates a new Job Title.
    */
    public function createDynamicModelData($modelName, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->dynamicModelDataService->createDynamicModelData($modelName, $data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all job titles
     */
    public function getAllDynamicModelData($modelName, Request $request)
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
        $result = $this->dynamicModelDataService->getAllDynamicModelData($modelName, $permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single job title based on id.
    */
    public function getDynamicModelData($modelName, $id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicModelDataService->getDynamicModelData($modelName, $id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single job title based on keyword.
    */
    public function getDynamicModelDataListByKeyword($modelName, $keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->dynamicModelDataService->getDynamicModelDataListByKeyword($modelName, $keyword);
        return $this->jsonResponse($result);
    }

    /*
        A single job title is updated.
    */
    public function updateDynamicModelData($modelName, $id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicModelDataService->updateDynamicModelData($modelName, $id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Job Title.
    */
    public function deleteDynamicModelData($modelName, $id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicModelDataService->softDeleteDynamicModelData($modelName, $id);
        return $this->jsonResponse($result);
    }
}
