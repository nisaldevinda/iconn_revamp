<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QualificationService;

/*
    Name: QualificationController
    Purpose: Performs request handling tasks related to the qualification model.
    Description: API requests related to the qualification model are directed to this controller.
    Module Creator: Chalaka
*/

class QualificationController extends Controller
{
    protected $qualificationService;

    /**
     * QualificationController constructor.
     *
     * @param qualificationService $qualificationService
     */
    public function __construct(QualificationService $qualificationService)
    {
        $this->qualificationService  = $qualificationService;
    }


    /*
        Creates a new qualification.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->qualificationService->createqualification($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all qualifications
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
        $result = $this->qualificationService->getAllqualifications($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single qualification based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationService->getqualification($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single qualification based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->qualificationService->getqualificationByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single qualification is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationService->updatequalification($id, $request->all());
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

        $result = $this->qualificationService->softDeletequalification($id);
        return $this->jsonResponse($result);
    }
}
