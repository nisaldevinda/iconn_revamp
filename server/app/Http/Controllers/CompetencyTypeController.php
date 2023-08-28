<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompetencyTypeService;

/*
    Name: CompetencyTypeController
    Purpose: Performs request handling tasks related to the Competency Type model.
    Description: API requests related to the CompetencyType model are directed to this controller.
    Module Creator: Sameera
*/

class CompetencyTypeController extends Controller
{
    protected $competencyTypeService;

    /**
     * CompetencyTypeServiceController constructor.
     *
     * @param CompetencyTypeService $competencyTypeService
     */
    public function __construct(CompetencyTypeService $competencyTypeService)
    {
        $this->competencyTypeService  = $competencyTypeService;
    }


    /*
        Creates a new CompetencyTypes.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->competencyTypeService->createCompetencyType($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all CompetencyType
     */
    public function getAllCompetencyTypes(Request $request)
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
        $result = $this->competencyTypeService->getAllCompetencyTypes($permittedFields,$options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single CompetencyType based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->competencyTypeService->getCompetencyType($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single CompetencyType based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->competencyTypeService->getCompetencyTypeByKeyword($keyword);
        return $this->jsonResponse($result);
    }


    /*
        A single CompetencyType is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->competencyTypeService->updateCompetencyType($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a CompetencyType
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->competencyTypeService->softDeleteCompetencyType($id);
        return $this->jsonResponse($result);
    }
}
