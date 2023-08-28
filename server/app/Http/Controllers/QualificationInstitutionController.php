<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QualificationInstitutionService;

/*
    Name: QualificationInstitutionController
    Purpose: Performs request handling tasks related to the QualificationInstitution model.
    Description: API requests related to the QualificationInstitution model are directed to this controller.
    Module Creator: Chalaka
*/

class QualificationInstitutionController extends Controller
{
    protected $qualificationInstitutionService;

    /**
     * QualificationInstitutionController constructor.
     *
     * @param qualificationInstitutionService $qualificationInstitutionService
     */
    public function __construct(QualificationInstitutionService $qualificationInstitutionService)
    {
        $this->qualificationInstitutionService  = $qualificationInstitutionService;
    }


    /*
        Creates a new QualificationInstitution.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->qualificationInstitutionService->createQualificationInstitution($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all QualificationInstitutions
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
        $result = $this->qualificationInstitutionService->getAllQualificationInstitutions($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single QualificationInstitution based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationInstitutionService->getQualificationInstitution($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single QualificationInstitution based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->qualificationInstitutionService->getQualificationInstitutionByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single QualificationInstitution is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->qualificationInstitutionService->updateQualificationInstitution($id, $request->all());
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

        $result = $this->qualificationInstitutionService->softDeleteQualificationInstitution($id);
        return $this->jsonResponse($result);
    }
}
