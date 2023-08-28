<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QualificationLevelService;

/*
    Name: QualificationLevelController
    Purpose: Performs request handling tasks related to the qualificationLevel model.
    Description: API requests related to the qualificationLevel model are directed to this controller.
    Module Creator: Chalaka
*/

class QualificationLevelController extends Controller
{
    protected $qualificationLevelService;

    /**
     * QualificationLevelController constructor.
     *
     * @param qualificationLevelService $qualificationLevelService
     */
    public function __construct(QualificationLevelService $qualificationLevelService)
    {
        $this->qualificationLevelService  = $qualificationLevelService;
    }


    /*
        Creates a new QualificationLevel.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->qualificationLevelService->createqualificationLevel($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all QualificationLevels
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
        $result = $this->qualificationLevelService->getAllqualificationLevels($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single QualificationLevel based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationLevelService->getQualificationLevel($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single QualificationLevel based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->qualificationLevelService->getQualificationLevelByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    public function getAllRawqualificationLevels(){
        $result = $this->qualificationLevelService->getRawAllQualificationLevels();
        return $this->jsonResponse($result);
    }

    /*
        A single QualificationLevel is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationLevelService->updateQualificationLevel($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a qualification level.
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationLevelService->softDeleteQualificationLevel($id);
        return $this->jsonResponse($result);
    }
}
