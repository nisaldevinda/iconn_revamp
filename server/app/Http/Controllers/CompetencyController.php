<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompetencyService;

/*
    Name: CompetencyController
    Purpose: Performs request handling tasks related to the Competency Type model.
    Description: API requests related to the Competency model are directed to this controller.
    Module Creator: Sameera
*/

class CompetencyController extends Controller
{
    protected $CompetencyService;

    /**
     * CompetencyServiceController constructor.
     *
     * @param CompetencyService $CompetencyService
     */
    public function __construct(CompetencyService $CompetencyService)
    {
        $this->CompetencyService  = $CompetencyService;
    }


    /*
        Creates a new Competencies.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->CompetencyService->createCompetency($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Competency
     */
    public function getAllCompetencies(Request $request)
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
        $result = $this->CompetencyService->getAllCompetency($permittedFields,$options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Competency based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->CompetencyService->getAllCompetency($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Competency based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->CompetencyService->getCompetencyByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    
    /*
        A single Competency is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->CompetencyService->updateCompetency($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Competency
    */
    public function delete($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->CompetencyService->softDeleteCompetency($id);
        return $this->jsonResponse($result);
    }
}
