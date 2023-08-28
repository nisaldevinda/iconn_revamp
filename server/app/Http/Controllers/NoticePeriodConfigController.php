<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NoticePeriodConfigService;

/*
    Name: NoticePeriodConfigController
    Purpose: Performs request handling tasks related to the NoticePeriodConfig model.
    Description: API requests related to the NoticePeriodConfig model are directed to this controller.
    Module Creator: Chalaka
*/

class NoticePeriodConfigController extends Controller
{
    protected $noticePeriodConfigService;

    /**
     * NoticePeriodConfigController constructor.
     *
     * @param NoticePeriodConfigService $noticePeriodConfigService
     */
    public function __construct(NoticePeriodConfigService $noticePeriodConfigService)
    {
        $this->noticePeriodConfigService  = $noticePeriodConfigService;
    }


    /*
        Creates a new NoticePeriodConfig.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->noticePeriodConfigService->createNoticePeriodConfig($data);
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
        $result = $this->noticePeriodConfigService->getAllNationalities($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single NoticePeriodConfig based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticePeriodConfigService->getNoticePeriodConfig($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single NoticePeriodConfig based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->noticePeriodConfigService->getNoticePeriodConfigByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single NoticePeriodConfig is updated.
    */
    public function update($id, Request $request)
    {
         $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticePeriodConfigService->updateNoticePeriodConfig($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a NoticePeriodConfig
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticePeriodConfigService->softDeleteNoticePeriodConfig($id);
        return $this->jsonResponse($result);
    }
}
