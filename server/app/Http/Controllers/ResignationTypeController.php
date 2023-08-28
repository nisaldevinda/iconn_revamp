<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResignationTypeService;

/*
    Name: ResignationTypeController
    Purpose: Performs request handling tasks related to the ResignationType model.
    Description: API requests related to the ResignationType model are directed to this controller.
    Module Creator: Hashan
*/

class ResignationTypeController extends Controller
{
    protected $resignationType;

    /**
     * ResignationTypeController constructor.
     *
     * @param ResignationTypeService $resignationType
     */
    public function __construct(ResignationTypeService $resignationType)
    {
        $this->resignationType  = $resignationType;
    }


    /*
        Creates a new ResignationType.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->resignationType->createResignationType($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all ResignationTypes
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
        $result = $this->resignationType->getAllResignationTypes($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single ResignationType based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->resignationType->getResignationType($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single ResignationType based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->resignationType->getResignationTypeByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single ResignationType is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->resignationType->updateResignationType($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a ResignationType
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->resignationType->softDeleteResignationType($id);
        return $this->jsonResponse($result);
    }
}
